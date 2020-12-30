<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\Tag;
use App\Form\RsvnViewType;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\MyUtils;
use DateTimeImmutable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/reservation/view")
 */
class ReservationViewController extends AbstractController
{
    /**
     * @Route("/week/{id?}/{date}", name="reservation_view_week", defaults={"date":"now"})
     * @ParamConverter("room", options={"strip_null":true})
     */
    public function week(
        Room $room = null,
        DateTimeImmutable $date = null,
        Request $request,
        ReservationRepository $rsvnRepo,
        RoomRepository $roomRepo
    ) {
        $session = $request->getSession();
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
            if (null === $room && null !== ($lastRoomId = $session->get('last_room_id'))) {
                $room = $roomRepo->find($lastRoomId);
                MyUtils::updateForm($form, 'room', TextType::class, ['data' => $room]);
            }
            $date = $session->get('last_date', $date);
            MyUtils::updateForm($form, 'date', DateType::class, ['data' => $date]);
        }
        $tableView = null;
        if ((null !== $room) && (null !== $date)) {
            $d = $date->modify('next monday');
            $tableView = $rsvnRepo->getTableByRoom($room->getId(), $d->modify('last monday'), $d);
        }

        return $this->render('reservation/view/week.html.twig', [
            'form' => $form->createView(),
            'room' => $room,
            'chosen_day' => $date ? $date->format('z') : null,
            'table_view' => $tableView,
        ]);
    }

    /**
     * @Route("/day", name="reservation_view_day")
     */
    public function day(Request $request, ReservationRepository $rsvnRepo, TagRepository $tagRepo)
    {
        $session = $request->getSession();
        $searchTags = $tagRepo->findBy(['search' => 1]);
        $form = $this->createForm(RsvnViewType::class, null, [
            'route_name' => $request->attributes->get('_route'),
            'tags' => $searchTags,
            'date' => new DateTimeImmutable(),
        ]);
        $form->handleRequest($request);
        $tagIds = [];
        $tagInt = $date = null;
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
            $tagIds = $session->get('last_tag_ids', $tagIds);
            if (count($tagIds)) {
                MyUtils::updateForm($form, 'tags', EntityType::class, [
                    'data' => array_filter($searchTags, function (Tag $tag) use ($tagIds) {
                        return in_array($tag->getId(), $tagIds);
                    }),
                ]);
            }
            $tagInt = $session->get('last_tag_int', true);
            MyUtils::updateForm($form, 'tag_intersect', ChoiceType::class, ['data' => $tagInt]);
            $date = $session->get('last_date', new DateTimeImmutable());
            MyUtils::updateForm($form, 'date', DateType::class, ['data' => $date]);
        }
        $tableView = null;
        if (count($tagIds) && (null !== $tagInt) && (null !== $date)) {
            $tableView = $rsvnRepo->getTableByDay($date, $tagIds, $tagInt);
        }

        return $this->render('reservation/view/day.html.twig', [
            'form' => $form->createView(),
            'table_view' => $tableView,
        ]);
    }

    /**
     * @Route("/user/{id?}/{date}", name="reservation_view_user", defaults={"date":"now"})
     * @ParamConverter("user", options={"strip_null":true})
     */
    public function user(
        Room $user = null,
        DateTimeImmutable $date = null,
        Request $request,
        ReservationRepository $rsvnRepo,
        UserRepository $userRepo
    ) {
        $user = $user ?? $this->getUser();
        $session = $request->getSession();
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
            $session->set('last_date', $date);
        }
        if (!$form->isSubmitted()) {
            $date = $session->get('last_date', $date);
            MyUtils::updateForm($form, 'date', DateType::class, ['data' => $date]);
        }
        $tableView = null;
        if ((null !== $user) && (null !== $date)) {
            $d = $date->modify('next monday');
            $tableView = $rsvnRepo->getTableByUser($user->getId(), $d->modify('last monday'), $d);
        }

        return $this->render('reservation/view/user.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'chosen_day' => $date ? $date->format('z') : null,
            'table_view' => $tableView,
        ]);
    }
}
