<?php

namespace App\Controller;

use App\Entity\Room;
use App\Form\RoomType;
use App\Repository\RoomRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/room")
 */
class RoomController extends AbstractController
{
    /**
     * @Route("/index", name="room_index")
     */
    public function index(RoomRepository $roomRepository)
    {
        return $this->render('room/index.html.twig', ['rooms' => $roomRepository->findAll()]);
    }

    /**
     * @Route("/add", name="room_add")
     */
    public function add(Request $request)
    {
        $form = $this->createForm(RoomType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($form->getData());
            $em->flush();

            return $this->redirectToRoute('room_index');
        }

        return $this->render('room/add.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/edit/{id}", name="room_edit")
     */
    public function edit(Room $room, Request $request)
    {
        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em = $this->getDoctrine()->getManager();
                if ('delete' === $request->get('delete')) {
                    $em->remove($room);
                }
                $em->flush();

                return $this->redirectToRoute('room_index');
            } catch (ForeignKeyConstraintViolationException $e) {
                return $this->render('main/redirect.html.twig', [
                    'path' => 'room_edit',
                    'params' => ['id' => $room->getId()],
                    'content' => 'Nie można usunąć sali, 
                        do której przyporządkowane są rezerwacje.',
                ]);
            }
        }

        return $this->render('room/edit.html.twig', [
            'form' => $form->createView(),
            'room' => $room,
        ]);
    }
}
