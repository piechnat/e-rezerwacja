<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\Tag;
use App\Form\RsvnViewType;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\TagRepository;
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
     * @Route("/week/{id}/{date}", name="reservation_view_week", defaults={"id":null})
     * @ParamConverter("room", options={"strip_null":true})
     */
    public function week(
        Room $room = null,
        DateTimeImmutable $date = null,
        Request $request,
        ReservationRepository $rsvnRepo,
        RoomRepository $roomRepo
    ) {
        $form = $this->createForm(RsvnViewType::class, null, [
            'route_name' => 'reservation_view_week',
            'room' => $room,
            'date' => $date,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $room = $formData['room'];
            $date = $formData['date'];
        }

        $session = $request->getSession();
        if (null !== $room) {
            $session->set('last_room_id', $room->getId());
        } else {
            $lastRoomId = $session->get('last_room_id');
            if ($lastRoomId && !$form->isSubmitted()) {
                $room = $roomRepo->find($lastRoomId);
                MyUtils::updateForm($form, 'room', TextType::class, ['data' => $room]);
            }
        }
        if (null !== $date) {
            $session->set('last_date', $date);
        } else {
            $date = $session->get('last_date', new DateTimeImmutable());
            if (!$form->isSubmitted()) {
                MyUtils::updateForm($form, 'date', DateType::class, ['data' => $date]);
            }
        }

        $tableView = null;
        if ($room && $date) {
            $d = $date->modify('next monday');
            $tableView = $rsvnRepo->getTableByRoom($room->getId(), $d->modify('last monday'), $d);
        }

        return $this->render('reservation/view/week.html.twig', [
            'form' => $form->createView(),
            'table_view' => $tableView,
            'chosen_day' => $date->format('z'),
        ]);
    }

    /**
     * @Route("/day", name="reservation_view_day")
     */
    public function day(Request $request, ReservationRepository $rsvnRepo, TagRepository $tagRepo)
    {
        $searchTags = $tagRepo->findBy(['search' => 1]);
        $form = $this->createForm(RsvnViewType::class, null, [
            'route_name' => 'reservation_view_day',
            'tags' => $searchTags,
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
        }

        $session = $request->getSession();
        if (count($tagIds)) {
            $session->set('last_tag_ids', $tagIds);
        } else {
            $tagIds = $session->get('last_tag_ids', []);
            if (count($tagIds) && !$form->isSubmitted()) {
                MyUtils::updateForm($form, 'tags', EntityType::class, [
                    'data' => array_filter($searchTags, function (Tag $tag) use ($tagIds) {
                        return in_array($tag->getId(), $tagIds);
                    }),
                ]);
            }
        }
        if (null !== $tagInt) {
            $session->set('last_tag_int', $tagInt);
        } else {
            $tagInt = $session->get('last_tag_int', true);
            if (!$form->isSubmitted()) {
                MyUtils::updateForm($form, 'tag_intersect', ChoiceType::class, ['data' => $tagInt]);
            }
        }
        if (null !== $date) {
            $session->set('last_date', $date);
        } else {
            $date = $session->get('last_date', new DateTimeImmutable());
            if (!$form->isSubmitted()) {
                MyUtils::updateForm($form, 'date', DateType::class, ['data' => $date]);
            }
        }

        $tableView = null;
        if (count($tagIds) && $date) {
            $tableView = $rsvnRepo->getDayTable($date, $tagIds, $tagInt);
        }

        return $this->render('reservation/view/day.html.twig', [
            'form' => $form->createView(),
            'table_view' => $tableView,
        ]);
    }

    /**
     * @Route("/user", name="reservation_view_user")
     */
    public function user()
    {
        return $this->render('main/redirect.html.twig', [
            'main_title' => 'Rezerwacje użytkownika',
            'main_content' => 'Under construction',
        ]);
    }
}
