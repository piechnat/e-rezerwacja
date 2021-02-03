<?php

namespace App\Controller;

use App\CustomTypes\ReservationNotAllowedException;
use App\CustomTypes\ReservationNotPossibleException;
use App\CustomTypes\UserLevel;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Service\MyUtils;
use App\Service\ReservationHelper;
use DateTimeImmutable;
use Doctrine\DBAL\Driver\DrizzlePDOMySql\Connection;
use Doctrine\DBAL\LockMode;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/reservation")
 */
class ReservationController extends AbstractController
{
    /**
     * @Route("/", name="reservation_index")
     */
    public function index(ReservationRepository $rsvnRepo)
    {
        return $this->render('reservation/index.html.twig', [
            'item_list' => $rsvnRepo->findAll(),
        ]);
    }

    /**
     * @Route("/show/{id}", name="reservation_show")
     */
    public function show(Reservation $rsvn)
    {
        return $this->render('reservation/show.html.twig', [
            'rsvn' => $rsvn,
            'can_edit_rsvn' => $this->canEditReservation($rsvn),
        ]);
    }

    /**
     * @Route("/delete/{id}", name="reservation_delete")
     */
    public function delete(Reservation $rsvn, Request $request)
    {
        if (
            $this->canEditReservation($rsvn)
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
     * @Route("/add/{room_id}/{beginTime}/{endTime}", name="reservation_add",
     *     defaults={"room_id": 0, "beginTime": "now"})
     * @Entity("room", class="App:Room", expr="room_id > 0 ? repository.find(room_id) : null")
     */
    public function add(
        Room $room = null,
        DateTimeImmutable $beginTime,
        DateTimeImmutable $endTime = null,
        Request $request,
        ReservationHelper $rsvnHelper
    ) {
        $rsvn = new Reservation();
        if ($room) {
            $rsvn->setRoom($room);
        }
        $rsvn->setRequester($this->getUser());
        $rsvn->setBeginTime($beginTime);
        $rsvn->setEndTime($endTime ?? $beginTime->modify('+60 minutes'));

        return $this->handleAddOrEdit($rsvn, true, $request, $rsvnHelper);
    }

    /**
     * @Route("/edit/{rsvn_id}", name="reservation_edit")
     * @ParamConverter("rsvn", class="App:Reservation", options={"id": "rsvn_id"})
     */
    public function edit(Reservation $rsvn, Request $request, ReservationHelper $rsvnHelper)
    {
        if ($this->canEditReservation($rsvn)) {
            return $this->handleAddOrEdit($rsvn, false, $request, $rsvnHelper);
        }

        throw $this->createAccessDeniedException();
    }

    private function handleAddOrEdit(
        Reservation $rsvn,
        bool $actionAdd,
        Request $request,
        ReservationHelper $rsvnHelper
    ) {
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
                    $em = $this->getDoctrine()->getManager();
                    // SELECT ... FOR UPDATE
                    $em->find(Room::class, $rsvn->getRoom()->getId(), LockMode::PESSIMISTIC_WRITE);
                    $rsvnHelper->checkConflicts($rsvn);

                    $em->persist($rsvn);
                    $em->flush();
                    $conn->commit();

                    return $this->redirectToRoute('reservation_show', ['id' => $rsvn->getId()]);
                } catch (Exception $e) {
                    $conn->rollback();

                    throw $e;
                }
            } catch (ReservationNotPossibleException $e) {
                $form->get('room')->addError($rsvnHelper->createFormError($e));
            } catch (ReservationNotAllowedException $e) {
                if (!isset($request->get('reservation', [])['send_request'])) {
                    $formSendRequest = true;
                    $form->addError($rsvnHelper->createFormError($e));
                } else {
                    return $this->forward('App\Controller\RequestController::add', [
                        'rsvn' => $rsvn,
                    ]);
                }
            }
        }
        if (!$form->isSubmitted() && !$rsvn->getRoom()) {
            if (null !== ($lastRoomId = $session->get('last_room_id'))) {
                MyUtils::updateForm($form, 'room', TextType::class, [
                    'data' => $this->getDoctrine()->getRepository(Room::class)->find($lastRoomId),
                ]);
            }
        }

        return $this->render('reservation/add-edit.html.twig', [
            'rsvn' => $rsvn,
            'action_add' => $actionAdd,
            'form' => $form->createView(),
            'send_request' => $formSendRequest,
        ]);
    }

    private function canEditReservation(Reservation $rsvn): bool
    {
        if ($rsvn->getEndTime() < new DateTimeImmutable()) {
            return false;
        }
        if ($this->getUser()->getId() === $rsvn->getRequester()->getId()) {
            return true;
        }

        return $this->isGranted(UserLevel::ADMIN);
    }
}
