<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\Tag;
use App\Entity\User;
use App\Form\RsvnViewType;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\AppHelper;
use DateTimeImmutable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/reservation/view")
 */
class RsvnViewController extends AbstractController
{
    /**
     * @Route("/week/{id?}/{date}", name="rsvn_view_week")
     * @ParamConverter("room", options={"strip_null":true})
     */
    public function week(
        Room $room = null,
        DateTimeImmutable $date = null,
        Request $request,
        ReservationRepository $rsvnRepo,
        RoomRepository $roomRepo
    ): Response {
        $session = AppHelper::initSession($request);
        $form = $this->createForm(RsvnViewType::class, null, [
            'route_name' => $request->attributes->get('_route'),
            'room' => $room,
            'date' => $date,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $room = $formData['room'];
            $date = $formData['date'];
            $session->set('last_room_id', $room->getId());
            $session->set('last_date', $date);
        }
        if (!$form->isSubmitted()) {
            if (!$room && null !== ($lastRoomId = $session->get('last_room_id'))) {
                $room = $roomRepo->find($lastRoomId);
                AppHelper::updateForm($form, 'room', TextType::class, ['data' => $room]);
            }
            if (!$date) {
                $date = $session->get('last_date', new DateTimeImmutable());
                AppHelper::updateForm($form, 'date', DateType::class, ['data' => $date]);
            }
        }
        $tableView = null;
        if ($room && $date) {
            $d = $date->modify('next monday');
            $tableView = $rsvnRepo->getTableByRoom($room->getId(), $d->modify('last monday'), $d);
        }

        return $this->render('reservation/view/week.html.twig', [
            'form' => $form->createView(),
            'room' => $room,
            'table_view' => $tableView,
        ]);
    }

    /**
     * @Route("/day/{date}/{roomId}", name="rsvn_view_day")
     */
    public function day(
        DateTimeImmutable $date = null,
        int $roomId = null,
        Request $request,
        ReservationRepository $rsvnRepo,
        TagRepository $tagRepo
    ): Response {
        $session = AppHelper::initSession($request);
        $searchTags = $tagRepo->findBy(['search' => true]);
        $form = $this->createForm(RsvnViewType::class, null, [
            'route_name' => $request->attributes->get('_route'),
            'tags' => $searchTags,
            'date' => $date,
        ]);
        $form->handleRequest($request);
        $tagIds = [];
        $tagInt = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            foreach ($formData['tags'] as $tag) {
                $tagIds[] = $tag->getId();
            }
            $tagInt = $formData['tag_intersect'];
            $date = $formData['date'];
            $session->set('last_tag_ids', $tagIds);
            $session->set('last_tag_int', $tagInt);
            $session->set('last_date', $date);
        }
        if (!$form->isSubmitted()) {
            if (!count($tagIds)) {
                $tagIds = $roomId ? $tagRepo->getTagIdsByRoomId($roomId, true)
                    : $session->get('last_tag_ids', $tagIds);
                if (count($tagIds)) {
                    AppHelper::updateForm($form, 'tags', EntityType::class, [
                        'data' => array_filter($searchTags, function (Tag $tag) use ($tagIds) {
                            return in_array($tag->getId(), $tagIds);
                        }),
                    ]);
                }
            }
            if (!$date) {
                $date = $session->get('last_date', new DateTimeImmutable());
                AppHelper::updateForm($form, 'date', DateType::class, ['data' => $date]);
            }
            $tagInt = $session->get('last_tag_int', true);
            AppHelper::updateForm($form, 'tag_intersect', ChoiceType::class, ['data' => $tagInt]);
        }
        $tableView = null;
        if (count($tagIds) && (null !== $tagInt) && $date) {
            $tableView = $rsvnRepo->getTableByDay($date, $tagIds, $tagInt);
        }

        return $this->render('reservation/view/day.html.twig', [
            'room_id' => $roomId,
            'form' => $form->createView(),
            'table_view' => $tableView,
        ]);
    }

    /**
     * @Route("/user/{id?}/{date}", name="rsvn_view_user")
     * @ParamConverter("user", options={"strip_null":true})
     */
    public function user(
        User $user = null,
        DateTimeImmutable $date = null,
        Request $request,
        ReservationRepository $rsvnRepo,
        UserRepository $userRepo
    ): Response {
        $session = AppHelper::initSession($request);
        $form = $this->createForm(RsvnViewType::class, null, [
            'route_name' => $request->attributes->get('_route'),
            'user' => $user,
            'date' => $date,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $user = $formData['user'];
            $date = $formData['date'];
            $session->set('last_user_id', $user->getId());
            $session->set('last_date', $date);
        }
        if (!$form->isSubmitted()) {
            if (!$user) {
                $user = $this->getUser();
                if (null !== ($lastUserId = $session->get('last_user_id'))) {
                    $user = $userRepo->find($lastUserId);
                }
                AppHelper::updateForm($form, 'user', TextType::class, ['data' => $user]);
            }
            if (!$date) {
                $date = $session->get('last_date', new DateTimeImmutable());
                AppHelper::updateForm($form, 'date', DateType::class, ['data' => $date]);
            }
        }
        $tableView = null;
        if ($user && $date) {
            $d = $date->modify('next monday');
            $tableView = $rsvnRepo->getTableByUser($user->getId(), $d->modify('last monday'), $d);
        }

        return $this->render('reservation/view/user.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'table_view' => $tableView,
        ]);
    }
}
