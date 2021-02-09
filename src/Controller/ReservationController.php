<?php

namespace App\Controller;

use App\CustomTypes\ReservationNotAllowedException;
use App\CustomTypes\ReservationNotPossibleException;
use App\CustomTypes\UserLevel;
use App\Entity\Request as EntityRequest;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\Tag;
use App\Entity\User;
use App\Form\ReservationType;
use App\Service\AppMailer;
use App\Service\AppHelper;
use App\Service\ReservationHelper;
use DateTimeImmutable;
use Doctrine\DBAL\Driver\DrizzlePDOMySql\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/reservation")
 */
class ReservationController extends AbstractController
{
    private const ACTION_EDIT = 0;
    private const ACTION_ADD = 1;
    private const ACTION_ADD_RQST = 2;

    /**
     * @Route("/", name="reservation_index")
     */
    public function index()
    {
        return $this->redirectToRoute('reservation_add');
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
    public function delete(Reservation $rsvn, Request $request, AppMailer $mailer)
    {
        if (
            $this->canEditReservation($rsvn)
            && $this->isCsrfTokenValid('reservation_delete', $request->request->get('token'))
        ) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($rsvn);
            $em->flush();
            if ($this->getUser() !== $rsvn->getRequester()) {
                $mailer->notify('Usunięcie rezerwacji', 'Twoja rezerwacja sali %rsvn_room% w dniu '.
                    '%rsvn_date% została usunięta przez %user%.', $rsvn);
            }

            return $this->redirectToRoute('reservation_view_week', [
                'id' => $rsvn->getRoom()->getId(),
                'date' => $rsvn->getBeginTime()->format('Y-m-d'),
            ]);
        }

        throw $this->createAccessDeniedException();
    }

    /**
     * @Route("/add/request/{id}", name="reservation_add_request")
     * @IsGranted(UserLevel::ADMIN)
     */
    public function addRequest(
        EntityRequest $rqst,
        Request $request,
        ReservationHelper $rsvnHelper,
        AppMailer $mailer
    ) {
        $rsvn = new Reservation();
        $rsvn->setRequester($rqst->getRequester());
        $rsvn->setRoom($rqst->getRoom());
        $rsvn->setBeginTime($rqst->getBeginTime());
        $rsvn->setEndTime($rqst->getEndTime());
        $rsvn->setDetails($rqst->getDetails());

        $resp = $this->handleReservation($rsvn, static::ACTION_ADD_RQST, $request, $rsvnHelper);
        if ($this->isSuccessfulResponse($resp)) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($rqst);
            $em->flush();
            if ($this->getUser() !== $rqst->getRequester()) {
                $mailer->notify('Dodanie rezerwacji', 'w odpowiedzi na Twój wniosek o rezerwację '.
                    'sali (%rqst_room% w dniu %rqst_date%) %user% zarezerwował(a) dla Ciebie '.
                    '%rsvn_room% w dniu %rsvn_date%. %rsvn_url%', $rsvn, $rqst);
            }
        }

        return $resp;
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
        ReservationHelper $rsvnHelper,
        AppMailer $mailer
    ) {
        $rsvn = new Reservation();
        if ($room) {
            $rsvn->setRoom($room);
        }
        $rsvn->setRequester($this->getUser());
        $rsvn->setBeginTime($beginTime);
        $rsvn->setEndTime($endTime ?? $beginTime->modify('+60 minutes'));

        $resp = $this->handleReservation($rsvn, static::ACTION_ADD, $request, $rsvnHelper);
        if (
            $this->isSuccessfulResponse($resp) 
            && $this->getUser() !== $rsvn->getRequester()
        ) {
            $mailer->notify('Dodanie rezerwacji', '%user% zarezerwował(a) dla Ciebie salę '.
                '%rsvn_room% w dniu %rsvn_date%. %rsvn_url%', $rsvn);
        }

        return $resp;
    }

    /**
     * @Route("/edit/{rsvn_id}", name="reservation_edit")
     * @ParamConverter("rsvn", class="App:Reservation", options={"id": "rsvn_id"})
     */
    public function edit(
        Reservation $rsvn,
        Request $request,
        ReservationHelper $rsvnHelper,
        AppMailer $mailer
    ) {
        if ($this->canEditReservation($rsvn)) {
            $originalRqstr = $rsvn->getRequester();
            $resp = $this->handleReservation($rsvn, static::ACTION_EDIT, $request, $rsvnHelper);

            if ($this->isSuccessfulResponse($resp)) {
                $rqstrIsChanged = $originalRqstr !== $rsvn->getRequester();
                if ($this->getUser() !== $rsvn->getRequester() || $rqstrIsChanged) {
                    $text = 'rezerwacja sali została zmodyfikowana przez %user%. %rsvn_url%';
                    $mailer->notify('Edycja rezerwacji', $text, $rsvn);
                    if ($rqstrIsChanged) {
                        $mailer->notify('Edycja rezerwacji', $text, $rsvn, null, $originalRqstr);
                    }
                }
            }

            return $resp;
        }

        throw $this->createAccessDeniedException();
    }

    private function handleReservation(
        Reservation $rsvn,
        int $action,
        Request $request,
        ReservationHelper $rsvnHelper
    ) {
        $session = $request->getSession();
        $formSendRequest = false;
        $formOptions = [
            'modify_requester' => $this->isGranted(UserLevel::ADMIN)
                && $action !== static::ACTION_ADD_RQST,
        ];
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
                    /** @var EntityManager */
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
                $form->addError($rsvnHelper->createFormError($e));
            } catch (ReservationNotAllowedException $e) {
                $formSendRequest = $action !== static::ACTION_ADD_RQST;
                if ($formSendRequest && isset($request->get('reservation', [])['send_request'])) {
                    return $this->forward('App\Controller\RequestController::add', [
                        'rsvn' => $rsvn,
                        'rsvnError' => $e->getMessage(),
                    ]);
                }
                $form->addError($rsvnHelper->createFormError($e));
            }
        }
        if (!$form->isSubmitted() && !$rsvn->getRoom()) {
            if (null !== ($lastRoomId = $session->get('last_room_id'))) {
                AppHelper::updateForm($form, 'room', TextType::class, [
                    'data' => $this->getDoctrine()->getRepository(Room::class)->find($lastRoomId),
                ]);
            }
        }

        return $this->render('reservation/add-edit.html.twig', [
            'rsvn' => $rsvn,
            'action_add' => $action !== static::ACTION_EDIT,
            'form' => $form->createView(),
            'send_request' => $formSendRequest,
        ]);
    }

    private function canEditReservation(Reservation $rsvn): bool
    {
        if ($rsvn->getEndTime() < new DateTimeImmutable()) {
            return false;
        }
        if ($this->getUser() === $rsvn->getRequester()) {
            return true;
        }
        if ($this->isGranted(UserLevel::ADMIN)) {
            return 0 === AppHelper::getMissingAccessLevel($this->getUser(), $rsvn->getRoom());
        }

        return false;
    }

    private function isSuccessfulResponse(Response $resp): bool
    {
        return $resp->isRedirection()
            && false !== strpos($resp->headers->get('Location'), '/reservation/show/');
    }
}
