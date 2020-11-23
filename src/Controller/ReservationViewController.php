<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Form\RsvnViewType;
use App\Repository\ReservationRepository;
use DateTimeImmutable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/reservation/view")
 */
class ReservationViewController extends AbstractController
{
    /**
     * @Route("/week/{id}/{date}", name="reservation_view_week", defaults={"id":null,"date":"now"})
     * @ParamConverter("room", options={"strip_null":true})
     */
    public function week(
        Room $room = null,
        DateTimeImmutable $date = null,
        Request $request,
        ReservationRepository $rsvnRepo
    ) {
        $form = $this->createForm(RsvnViewType::class, null, [
            'route_name' => 'reservation_view_week',
            'date' => $date,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $room = $form->get('room')->getData();
            $date = $form->get('date')->getData();
        } elseif ($room) {
            $form->get('room')->setData($room);
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
    public function day(Request $request, ReservationRepository $rsvnRepo)
    {
        $form = $this->createForm(RsvnViewType::class, null, [
            'route_name' => 'reservation_view_day',
        ]);
        $form->handleRequest($request);

        $tableView = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $tagIds = [];
            foreach ($form->get('tags')->getData()->getValues() as $tag) {
                $tagIds[] = $tag->getId();
            }
            $tableView = $rsvnRepo->getDayTable(
                $form->get('date')->getData(),
                $tagIds,
                $form->get('operation')->getData()
            );
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
            'title' => 'Rezerwacje użytkownika',
            'content' => 'Under construction',
        ]);
    }
}
