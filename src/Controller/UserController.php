<?php

namespace App\Controller;

use App\CustomTypes\Lang;
use App\CustomTypes\UserLevel;
use App\Entity\User;
use App\Form\UserToEmailTransformer;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\AppHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user_index")
     */
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', ['users' => $userRepository->findAll()]);
    }

    /**
     * @Route("/show", name="user_self_show")
     * @Route("/show/{id}", name="user_show")
     */
    public function show(
        User $user = null,
        Request $request,
        UserToEmailTransformer $userToEmail
    ): Response {
        $user = $user ?? $this->getUser();
        $builder = $this->createFormBuilder(null, ['csrf_protection' => false]);
        $builder->setAction($this->generateUrl('user_self_show'))->setMethod('GET')
            ->add('email', TextType::class, [
                'data' => $user,
                'data_class' => null,
                'label' => 'Pełna nazwa',
            ])
        ;
        $builder->get('email')->addModelTransformer($userToEmail);
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData()['email'];
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'lang' => Lang::getValue($user->getLang()),
            'can_edit_user' => $this->canEditUser($user),
            'userFullname' => $user->getFullname(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit", name="user_self_edit")
     * @Route("/edit/{id}", name="user_edit")
     */
    public function edit(User $user = null, Request $request): Response
    {
        $user = $user ?? $this->getUser();
        if (!$this->canEditUser($user)) {
            throw $this->createAccessDeniedException();
        }
        $unauthorizedTags = AppHelper::getUnauthorizedTags($this->getUser(), $user->getTags());
        $formOptions = [];
        $formOptions['admin_edit'] = $this->isGranted(UserLevel::ADMIN);
        $formOptions['access_names'] = array_flip(UserLevel::getValues());
        array_splice($formOptions['access_names'], $this->getUser()->getAccessLevel() + 1);
        $form = $this->createForm(UserType::class, $user, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            array_walk($unauthorizedTags, [$user, 'addTag']);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    private function canEditUser(User $user): bool
    {
        if ($this->getUser() === $user) {
            return true;
        }
        if ($this->isGranted(UserLevel::ADMIN)) {
            return $this->getUser()->getAccessLevel() > $user->getAccessLevel();
        }

        return false;
    }
}
