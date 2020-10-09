<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Service\AppHelper;
use App\Service\NotAllowedException;
use App\Service\ReservationConflictException;
use Doctrine\DBAL\Driver\DrizzlePDOMySql\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ReservationController extends AbstractController
{
    /**
     * @Route("/reservation/index", name="reservation_index")
     */
    public function reservation_index()
    {
        return $this->render('reservation/index.html.twig', [
            'item_list' => $this->getDoctrine()->getRepository(Reservation::class)->findAll(),
        ]);
    }

    /**
     * @Route("/reservation/show/{id}", name="reservation_show")
     */
    public function reservation_show(Reservation $rsvn)
    {
        return $this->render('reservation/show.html.twig', ['rsvn' => $rsvn]);
    }

    /**
     * @Route("/reservation/add", name="reservation_add")
     */
    public function reservation_add(Request $request, AppHelper $helper)
    {
        $rsvn = new Reservation();
        $rsvn->setRequester($this->getUser());
        $rsvn->setBeginTime(new \DateTime());
        $rsvn->setEndTime((new \DateTime())->modify('+60 minutes'));
        $rsvn->setDetails('Ćwiczenie');

        $formOptions = [
            'modify_requester' => true,
            'send_request' => false,
        ];
        $form = $this->createForm(ReservationType::class, $rsvn, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ReservationRepository */
            $repo = $this->getDoctrine()->getRepository(Reservation::class);
            $rsvn = $form->getData();

            try {
                $privilegesMsg = $helper->isReservationAllowed($rsvn);
                if (AppHelper::RSVN_ALLOWED_MSG === $privilegesMsg) {
                    /** @var EntityManagerInterface */
                    $mngr = $this->getDoctrine()->getManager();
                    /** @var Connection */
                    $conn = $this->getDoctrine()->getConnection();
                    $rsvn->setEditorId($this->getUser()->getId())->setEditTime(new \DateTime());
                    $conn->beginTransaction();

                    try {
                        $mngr->find(
                            Room::class,
                            $rsvn->getRoom()->getId(),
                            LockMode::PESSIMISTIC_WRITE // SELECT ... FOR UPDATE
                        );
                        $conflictIds = $repo->getConflictIds($rsvn);
                        if (count($conflictIds) > 0) {
                            throw new ReservationConflictException($conflictIds[0]);
                        }
                        $mngr->persist($rsvn);
                        $mngr->flush();
                        $conn->commit();

                        return $this->redirectToRoute('reservation_show', ['id' => $rsvn->getId()]);
                    } catch (Exception $e) {
                        $conn->rollback();

                        throw $e;
                    }
                } else {
                    $conflictIds = $repo->getConflictIds($rsvn);
                    if (count($conflictIds) > 0) {
                        throw new ReservationConflictException($conflictIds[0]);
                    }

                    throw new NotAllowedException($privilegesMsg);
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
