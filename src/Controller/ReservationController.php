<?php

namespace App\Controller;

use App\CustomTypes\ReservationConflictException;
use App\CustomTypes\ReservationNotAllowedException;
use App\CustomTypes\UserLevel;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Service\ReservationHelper;
use DateTimeImmutable;
use Doctrine\DBAL\Driver\DrizzlePDOMySql\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/reservation/add/{room_id}/{beginTime}/{endTime}", name="reservation_add",
     *     defaults={"room_id":null,"beginTime":"now","endTime":"now +60 minutes"})
     * @ParamConverter("room", class="App\Entity\Room", options={"id"="room_id"})
     *
     * @Route("/reservation/edit/{id}", name="reservation_edit")
     * @ParamConverter("rsvn", class="App\Entity\Reservation")
     */
    public function addOrEdit(
        Room $room = null,
        DateTimeImmutable $beginTime = null,
        DateTimeImmutable $endTime = null,
        Reservation $rsvn = null,
        Request $request,
        ReservationHelper $rsvnHelper
    ) {
        $actionAdd = 'reservation_add' === $request->attributes->get('_route');
        if ($actionAdd) {
            $rsvn = new Reservation();
            $rsvn->setRequester($this->getUser());
            $rsvn->setRoom($room);
            $rsvn->setBeginTime($beginTime);
            $rsvn->setEndTime($endTime);
        } elseif (!$actionAdd && null === $rsvn) {
            throw $this->createNotFoundException();
        }
        $formSendRequest = false;
        $formOptions = ['modify_requester' => $this->isGranted(UserLevel::ADMIN)];

        $form = $this->createForm(ReservationType::class, $rsvn, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rsvn = $form->getData();
            $rsvn->setEditor($this->getUser());
            $rsvn->setEditTime(new DateTimeImmutable());

            try {
                $rsvnHelper->checkConstraints($rsvn);

                /** @var Connection */
                $conn = $this->getDoctrine()->getConnection();
                $conn->beginTransaction();

                try {
                    /** @var EntityManagerInterface */
                    $mngr = $this->getDoctrine()->getManager();
                    $mngr->find(
                        Room::class,
                        $rsvn->getRoom()->getId(),
                        LockMode::PESSIMISTIC_WRITE // SELECT ... FOR UPDATE
                    );
                    $rsvnHelper->checkConflicts($rsvn);

                    $mngr->persist($rsvn);
                    $mngr->flush();
                    $conn->commit();

                    return $this->redirectToRoute('reservation_show', ['id' => $rsvn->getId()]);
                } catch (Exception $e) {
                    $conn->rollback();

                    throw $e;
                }
            } catch (ReservationConflictException $e) {
                $form->get('room')->addError($rsvnHelper->createFormError($e));
            } catch (ReservationNotAllowedException $e) {
                if (!isset($request->get('reservation', [])['send_request'])) {
                    $formSendRequest = true;
                    $form->addError($rsvnHelper->createFormError($e));
                } else {
                    return $this->render('main/redirect.html.twig', [
                        'path' => 'reservation_add',
                        'title' => 'Under construction',
                        'content' => 'Send request confirmation screen',
                    ]);
                }
            }
        }

        return $this->render('reservation/add-edit.html.twig', [
            'main_title' => ($actionAdd ? 'Dodawanie' : 'Edycja').' rezerwacji',
            'main_icon' => ($actionAdd ? 'bx bx-calendar-plus' : 'bx bx-calendar-edit'),
            'form' => $form->createView(),
            'send_request' => $formSendRequest,
        ]);
    }

    /**
     * @Route("/request", name="request")
     */
    public function requests()
    {
        return $this->render('main/redirect.html.twig', [
            'title' => 'Żądania rezerwacji',
            'content' => 'Under construction',
        ]);
    }
}
