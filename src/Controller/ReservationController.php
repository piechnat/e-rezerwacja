<?php

namespace App\Controller;

use App\CustomTypes\NotAllowedException;
use App\CustomTypes\ReservationConflictException;
use App\CustomTypes\ReservationError;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Service\AppHelper;
use DateTimeImmutable;
use Doctrine\DBAL\Driver\DrizzlePDOMySql\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class ReservationController extends AbstractController
{
    /**
     * @Route("/reservation/index", name="reservation_index")
     */
    public function index(ReservationRepository $rsvnRepo)
    {
        return $this->render('reservation/index.html.twig', [
            'item_list' => $rsvnRepo->findAll(),
        ]);
    }

    /**
     * @Route("/reservation/show/{id}", name="reservation_show")
     */
    public function show(Reservation $rsvn)
    {
        return $this->render('reservation/show.html.twig', ['rsvn' => $rsvn]);
    }

    /**
     * @Route("/reservation/add/{id}/{beginTime}/{endTime}", name="reservation_add",
     * defaults={"id":null,"beginTime":"now","endTime":"now +60 minutes"})
     * @ParamConverter("room", options={"strip_null":true})
     */
    public function add(
        Room $room = null,
        DateTimeImmutable $beginTime = null,
        DateTimeImmutable $endTime = null,
        Request $request,
        ReservationRepository $rsvnRepo,
        AppHelper $helper
    ) {
        $rsvn = new Reservation();
        $rsvn->setRequester($this->getUser());
        if ($room) $rsvn->setRoom($room);
        $rsvn->setBeginTime($beginTime);
        $rsvn->setEndTime($endTime);

        $formOptions = [
            'modify_requester' => true,
            'send_request' => false,
        ];
        $form = $this->createForm(ReservationType::class, $rsvn, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rsvn = $form->getData();

            try {
                $rsvnErr = $helper->isReservationAllowed($rsvn);
                if (ReservationError::RSVN_ALLOWED === $rsvnErr) {
                    /** @var EntityManagerInterface */
                    $mngr = $this->getDoctrine()->getManager();
                    /** @var Connection */
                    $conn = $this->getDoctrine()->getConnection();
                    $rsvn->setEditorId($this->getUser()->getId());
                    $rsvn->setEditTime(new DateTimeImmutable());
                    $conn->beginTransaction();

                    try {
                        $mngr->find(
                            Room::class,
                            $rsvn->getRoom()->getId(),
                            LockMode::PESSIMISTIC_WRITE // SELECT ... FOR UPDATE
                        );
                        $conflictIds = $rsvnRepo->getConflictIds($rsvn);
                        if (count($conflictIds) > 0) {
                            throw new ReservationConflictException($conflictIds[0]);
                        }
                        $mngr->persist($rsvn);
                        $mngr->flush();
                        $conn->commit();

                        return $this->redirectToRoute('reservation_show', ['id' => $rsvn->getId()]);
                    } catch (\Exception $e) {
                        $conn->rollback();

                        throw $e;
                    }
                } else {
                    $conflictIds = $rsvnRepo->getConflictIds($rsvn);
                    if (count($conflictIds) > 0) {
                        throw new ReservationConflictException($conflictIds[0]);
                    }

                    throw new NotAllowedException($rsvnErr);
                }
            } catch (ReservationConflictException $e) {
                $form->get('room')->addError($helper->createFormError($e));
            } catch (NotAllowedException $e) {
                if (!isset($request->get('reservation', [])['send_request'])) {
                    $formOptions['send_request'] = true;
                    $form->addError($helper->createFormError($e));
                } else {
                    return $this->render('main/redirect.html.twig', [
                        'path' => 'reservation_add',
                        'title' => 'Under construction',
                        'content' => 'Send request confirmation screen',
                    ]);
                }
            }
        }
        if ($form->isSubmitted()) {
            $errors = $form->getErrors(true);
            $form = $this->createForm(ReservationType::class, $form->getData(), $formOptions);
            foreach ($errors as $error) {
                $name = $error->getOrigin()->getName();
                $form->has($name) ? $form->get($name)->addError($error) : $form->addError($error);
            }
        }

        return $this->render('reservation/add.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/request", name="request")
     */
    public function requests()
    {
        return $this->render('main/redirect.html.twig');
    }
}
