<?php

namespace App\Controller;

use App\CustomTypes\UserRole;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/index", name="user_index")
     */
    public function user_index(UserRepository $userRepository)
    {
        return $this->render('user/index.html.twig', ['users' => $userRepository->findAll()]);
    }

    /**
     * @Route("/show", name="user_self_show")
     * @Route("/show/{id}", name="user_show")
     */
    public function user_show(User $user = null)
    {
        if (!$user) {
            $user = $this->getUser();
        }

        return $this->render('user/show.html.twig', ['user' => $user]);
    }

    /**
     * @Route("/edit", name="user_self_edit")
     * @Route("/edit/{id}", name="user_edit")
     */
    public function user_edit(User $user = null, Request $request)
    {
        if (!$user) {
            $user = $this->getUser();
        }
        $formOptions = ['edit_roles' => $this->isGranted(UserRole::ADMIN)];
        $form = $this->createForm(UserType::class, $user, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', ['form' => $form->createView()]);
    }
}
