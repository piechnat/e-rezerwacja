<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Form\ReservationViewType;
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
    public function reservation_view_week(
        Room $room = null,
        DateTimeImmutable $date = null,
        Request $request
    ) {
        $form = $this->createForm(ReservationViewType::class, null, [
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
            /** @var ReservationRepository */
            $repo = $this->getDoctrine()->getRepository(Reservation::class);
            $d = $date->modify('next monday');
            $tableView = $repo->getTableByRoom($room->getId(), $d->modify('last monday'), $d);
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
    public function reservation_view_day(Request $request)
    {
        $form = $this->createForm(ReservationViewType::class, null, [
            'route_name' => 'reservation_view_day',
        ]);
        $form->handleRequest($request);
        
        $tableView = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $tagIds = [];
            foreach ($form->get('tags')->getData()->getValues() as $tag) {
                $tagIds[] = $tag->getId();
            }
            /** @var ReservationRepository */
            $repo = $this->getDoctrine()->getRepository(Reservation::class);
            $tableView = $repo->getDayTable(
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
}
