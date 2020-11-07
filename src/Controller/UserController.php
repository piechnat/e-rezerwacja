<?php

namespace App\Controller;

use App\CustomTypes\Lang;
use App\CustomTypes\UserLevel;
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
    public function index(UserRepository $userRepository)
    {
        return $this->render('user/index.html.twig', ['users' => $userRepository->findAll()]);
    }

    /**
     * @Route("/show", name="user_self_show")
     * @Route("/show/{id}", name="user_show")
     */
    public function show(User $user = null)
    {
        if (!$user) {
            $user = $this->getUser();
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'lang' => Lang::getValue($user->getLang()),
            'can_modify' => $this->canModify($user),
        ]);
    }

    /**
     * @Route("/edit", name="user_self_edit")
     * @Route("/edit/{id}", name="user_edit")
     */
    public function edit(User $user = null, Request $request)
    {
        if (!$user) {
            $user = $this->getUser();
        }
        if (!$this->canModify($user)) {
            throw $this->createAccessDeniedException();
        }
        $formOptions = [];
        if ($this->isGranted(UserLevel::ADMIN)) {
            $formOptions['access_names'] = array_flip(UserLevel::getValues());
            array_splice($formOptions['access_names'], $this->getUser()->getAccessLevel() + 1);
        }
        $form = $this->createForm(UserType::class, $user, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', ['form' => $form->createView()]);
    }

    private function canModify(User $user): bool
    {
        if ($this->getUser()->getId() === $user->getId()) {
            return true;
        }
        if ($this->isGranted(UserLevel::ADMIN)) {
            return $this->getUser()->getAccessLevel() >= $user->getAccessLevel();
        }

        return false;
    }
}
