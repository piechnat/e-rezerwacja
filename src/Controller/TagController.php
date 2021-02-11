<?php

namespace App\Controller;

use App\CustomTypes\UserLevel;
use App\Entity\Tag;
use App\Form\TagType;
use App\Repository\TagRepository;
use App\Service\AppHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tag")
 */
class TagController extends AbstractController
{
    /**
     * @Route("/", name="tag_index")
     * @IsGranted(UserLevel::ADMIN)
     */
    public function index(TagRepository $tagRepo): Response
    {
        $tags = $tagRepo->findAll();

        return $this->render('tag/index.html.twig', [
            'tags' => $tags,
            'access_levels' => array_values(UserLevel::getValues()),
            'is_super_admin' => $this->isGranted(UserLevel::SUPER_ADMIN),
            'unauthorized_tags' => AppHelper::getUnauthorizedTags($this->getUser(), $tags),
        ]);
    }

    /**
     * @Route("/add", name="tag_add")
     * @IsGranted(UserLevel::SUPER_ADMIN)
     */
    public function add(Request $request): Response
    {
        $tag = new Tag();
        $form = $this->createForm(TagType::class, $tag, ['edit_mode' => 'entity']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($tag);
            $em->flush();

            return $this->redirectToRoute('tag_index');
        }

        return $this->render('tag/add.html.twig', [
            'tag' => $tag,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit/{id}/{mode}", name="tag_edit", requirements={"mode"="rooms|users"})
     */
    public function edit(Tag $tag, string $mode = 'entity', Request $request): Response
    {
        if (
            ('entity' === $mode && !$this->isGranted(UserLevel::SUPER_ADMIN))
            || $tag->getLevel() >= $this->getUser()->getAccessLevel()
        ) {
            throw $this->createAccessDeniedException();
        }
        $form = $this->createForm(TagType::class, $tag, ['edit_mode' => $mode]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            if ('delete' === $request->get('delete')) {
                $em->remove($tag);
            }
            $em->flush();

            return $this->redirectToRoute('tag_index');
        }

        return $this->render('tag/edit.html.twig', [
            'tag' => $tag,
            'form' => $form->createView(),
            'edit_mode' => $mode,
            'access_levels' => array_values(UserLevel::getValues()),
        ]);
    }
}
