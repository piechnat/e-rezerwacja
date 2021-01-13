<?php

namespace App\Controller;

use App\CustomTypes\ReservationConflictException;
use App\CustomTypes\ReservationNotAllowedException;
use App\CustomTypes\UserLevel;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Service\MyUtils;
use App\Service\ReservationHelper;
use DateTimeImmutable;
use Doctrine\DBAL\Driver\DrizzlePDOMySql\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
        return $this->render('reservation/show.html.twig', [
            'rsvn' => $rsvn,
            'can_edit_rsvn' => $this->canEditRsvn($rsvn),
        ]);
    }

    /**
     * @Route("/reservation/delete/{id}", name="reservation_delete")
     */
    public function delete(Reservation $rsvn, Request $request)
    {
        if (
            $this->canEditRsvn($rsvn)
            && $this->isCsrfTokenValid('reservation_delete', $request->request->get('token'))
        ) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($rsvn);
            $em->flush();

            return $this->redirectToRoute('reservation_view_week', [
                'id' => $rsvn->getRoom()->getId(),
                'date' => $rsvn->getBeginTime()->format('Y-m-d'),
            ]);
        }

        throw $this->createAccessDeniedException();
    }

    /**
     * @Route("/reservation/add/{room_id?}/{beginTime}/{endTime}", name="reservation_add",
     *     defaults={"beginTime":"now", "endTime":"now +60 minutes"})
     * @ParamConverter("room", class="App:Room", options={"id":"room_id"})
     *     
     * @Route("/reservation/edit/{rsvn_id}", name="reservation_edit")
     * @ParamConverter("rsvn", class="App:Reservation", options={"id":"rsvn_id"})
     */
    public function addOrEdit(
        Room $room = null,
        DateTimeImmutable $beginTime = null,
        DateTimeImmutable $endTime = null,
        Reservation $rsvn = null,
        Request $request,
        ReservationHelper $rsvnHelper,
        RoomRepository $roomRepo
    ) {
        $actionAdd = 'reservation_add' === $request->attributes->get('_route');
        if ($actionAdd) {
            $rsvn = new Reservation();
            $rsvn->setRequester($this->getUser());
            $rsvn->setRoom($room);
            $rsvn->setBeginTime($beginTime);
            $rsvn->setEndTime($endTime);
        } elseif (null === $rsvn) {
            throw $this->createNotFoundException();
        } elseif (false === $this->canEditRsvn($rsvn)) {
            throw $this->createAccessDeniedException();
        } else {
            $room = $rsvn->getRoom();
        }
        $session = $request->getSession();
        $formSendRequest = false;
        $formOptions = ['modify_requester' => $this->isGranted(UserLevel::ADMIN)];

        $form = $this->createForm(ReservationType::class, $rsvn, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rsvn = $form->getData();
            $rsvn->setEditor($this->getUser());
            $rsvn->setEditTime(new DateTimeImmutable());
            $session->set('last_room_id', $rsvn->getRoom()->getId());

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
                        'main_title' => 'Under construction',
                        'main_content' => 'Send request confirmation screen',
                    ]);
                }
            }
        }
        if (!$form->isSubmitted() && null === $room) {
            if (null !== ($lastRoomId = $session->get('last_room_id'))) {
                $room = $roomRepo->find($lastRoomId);
                MyUtils::updateForm($form, 'room', TextType::class, ['data' => $room]);
            }
        }

        return $this->render('reservation/add-edit.html.twig', [
            'rsvn' => $rsvn,
            'action_add' => $actionAdd,
            'form' => $form->createView(),
            'send_request' => $formSendRequest,
        ]);
    }

    /**
     * @Route("/requests", name="requests")
     */
    public function requests()
    {
        return $this->render('main/redirect.html.twig', [
            'main_title' => 'Żądania rezerwacji',
            'main_content' => 'Under construction',
        ]);
    }

    private function canEditRsvn(Reservation $rsvn): bool
    {
        if ($this->getUser()->getId() === $rsvn->getRequester()->getId()) {
            return true;
        }

        return $this->isGranted(UserLevel::ADMIN);
    }
}
