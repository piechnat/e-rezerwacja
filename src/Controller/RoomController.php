<?php

namespace App\Controller;

use App\Entity\Room;
use App\Form\RoomToTitleTransformer;
use App\Form\RoomType;
use App\Repository\RoomRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
     * @Route("/show", name="room_form_show")
     * @Route("/show/{id}", name="room_show")
     */
    public function show(Room $room = null, Request $request, RoomToTitleTransformer $roomToTitle)
    {
        $builder = $this->createFormBuilder(null, ['csrf_protection' => false]);
        $builder->setAction($this->generateUrl('room_form_show'))->setMethod('GET')
            ->add('room', EntityType::class, [
                'class' => Room::class,
                'label' => 'Nazwa sali',
                'placeholder' => "\u{200B}",
                'attr' => ['class' => 'jqslct2-single-select'],
            ])
        ;
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $room = $form->get('room')->getData();
        }

        return $this->render('room/show.html.twig', [
            'room' => $room,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/add", name="room_add")
     */
    public function add(Request $request)
    {
        $form = $this->createForm(RoomType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $room = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($room);
            $em->flush();

            return $this->redirectToRoute('room_show', ['id' => $room->getId()]);
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
