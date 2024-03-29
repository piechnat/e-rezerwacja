<?php

namespace App\Controller;

use App\CustomTypes\UserLevel;
use App\Entity\Room;
use App\Form\RoomType;
use App\Repository\RoomRepository;
use App\Service\AppHelper;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/room")
 */
class RoomController extends AbstractController
{
    /**
     * @Route("/", name="room_index")
     */
    public function index(RoomRepository $roomRepo): Response
    {
        return $this->render('room/index.html.twig', ['rooms' => $roomRepo->findAll()]);
    }

    /**
     * @Route("/show", name="room_form_show")
     * @Route("/show/{id}", name="room_show")
     */
    public function show(Room $room = null, Request $request, RoomRepository $roomRepo): Response
    {
        $session = AppHelper::initSession($request);
        $builder = $this->createFormBuilder(null, ['csrf_protection' => false]);
        $builder->setAction($this->generateUrl('room_form_show'))->setMethod('GET')
            ->add('room', EntityType::class, [
                'class' => Room::class,
                'data' => $room,
                'label' => 'Nazwa sali',
                'placeholder' => "\u{200B}",
            ])
        ;
        $form = $builder->getForm();
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $room = $form->getData()['room'];
            $session->set('last_room_id', $room->getId());
        }
        if (!$form->isSubmitted() && !$room) {
            if (null !== ($lastRoomId = $session->get('last_room_id'))) {
                $room = $roomRepo->find($lastRoomId);
                AppHelper::updateForm($form, 'room', EntityType::class, ['data' => $room]);
            }
        }

        return $this->render('room/show.html.twig', [
            'room' => $room,
            'can_edit_room' => $room ? $this->canEditRoom($room) : false,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/add", name="room_add")
     * @IsGranted(UserLevel::SUPER_ADMIN)
     */
    public function add(Request $request): Response
    {
        $form = $this->createForm(RoomType::class, null, [
            'route_name' => 'room_add',
            'data_class' => null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em = $this->getDoctrine()->getManager();
                $titles = array_filter(
                    array_map('trim', explode("\n", $form->getData()['titles']))
                );
                foreach ($titles as $title) {
                    $em->persist(new Room($title));
                }
                $em->flush();

                return $this->redirectToRoute('room_form_show');
            } catch (UniqueConstraintViolationException $e) {
                return $this->render('main/redirect.html.twig', [
                    'path' => 'room_add',
                    'main_content' => 'Jedna z podanych nazw już istnieje.',
                    'no_trans_msg' => $e->getMessage(),
                ]);
            }
        }

        return $this->render('room/add.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/delete/{id}", name="room_delete")
     * @IsGranted(UserLevel::SUPER_ADMIN)
     */
    public function delete(Room $room, Request $request): Response
    {
        if ($this->isCsrfTokenValid('room_delete', $request->request->get('token'))) {
            try {
                $em = $this->getDoctrine()->getManager();
                $em->remove($room);
                $em->flush();

                return $this->redirectToRoute('room_form_show');
            } catch (ForeignKeyConstraintViolationException $e) {
                return $this->render('main/redirect.html.twig', [
                    'path' => 'room_show',
                    'params' => ['id' => $room->getId()],
                    'main_content' => 'Nie można usunąć sali, '.
                        'do której przyporządkowane są rezerwacje.',
                ]);
            }
        }

        throw $this->createAccessDeniedException();
    }

    /**
     * @Route("/edit/{id}", name="room_edit")
     */
    public function edit(Room $room, Request $request): Response
    {
        if (!$this->canEditRoom($room)) {
            throw $this->createAccessDeniedException();
        }
        $unauthorizedTags = AppHelper::getUnauthorizedTags($this->getUser(), $room->getTags());
        $form = $this->createForm(RoomType::class, $room, ['route_name' => 'room_edit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            array_walk($unauthorizedTags, [$room, 'addTag']);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('room_show', ['id' => $room->getId()]);
        }

        return $this->render('room/edit.html.twig', [
            'form' => $form->createView(),
            'room' => $room,
        ]);
    }

    private function canEditRoom(Room $room): bool
    {
        return $this->isGranted(UserLevel::ADMIN) 
            && 0 === AppHelper::getMissingAccessLevel($this->getUser(), $room);
    }
}
