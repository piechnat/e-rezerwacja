<?php

namespace App\Controller;

use App\Entity\TimeConstraint;
use App\Form\TimeConstraintType;
use App\Repository\ConstraintRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/constraint")
 */
class ConstraintController extends AbstractController
{
    /**
     * @Route("/test", name="constraint_test", methods={"GET"})
     */
    public function test(ConstraintRepository $cstrRepo): Response
    {
        $arr = $cstrRepo->getOpeningHours(
            $this->getUser(),
            new DateTimeImmutable('01-01-2021'),
            new DateTimeImmutable('30-01-2021')
        );
        return $this->render('main/redirect.html.twig', [
            'main_title' => 'Test',
            'main_content' => '<pre>'.print_r($arr,1).'</pre>',
        ]);
    }

    /**
     * @Route("/index", name="constraint_index", methods={"GET"})
     */
    public function index(ConstraintRepository $cstrRepo): Response
    {
        //$cstrRepo->removeExpired();

        return $this->render('constraint/index.html.twig', [
            'time_constraints' => $cstrRepo->findAll(),
        ]);
    }

    /**
     * @Route("/add", name="constraint_add", methods={"GET","POST"})
     */
    public function add(Request $request): Response
    {
        $timeCstr = new TimeConstraint();
        $form = $this->createForm(TimeConstraintType::class, $timeCstr);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($timeCstr);
            $entityManager->flush();

            return $this->redirectToRoute('constraint_index');
        }

        return $this->render('constraint/add.html.twig', [
            'time_cstr' => $timeCstr,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit/{id}", name="constraint_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, TimeConstraint $timeCstr): Response
    {
        $form = $this->createForm(TimeConstraintType::class, $timeCstr);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            if ('delete' === $request->get('delete')) {
                $em->remove($timeCstr);
            }
            $em->flush();

            return $this->redirectToRoute('constraint_index');
        }

        return $this->render('constraint/edit.html.twig', [
            'time_cstr' => $timeCstr,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/reservation", name="constraint_reservation")
     */
    public function reservation(): Response
    {
        $cache = new PdoAdapter($this->getDoctrine()->getConnection());
        return $this->render('constraint/reservation.html.twig', [
            'text' => '>'. $cache->get('DUPA', function () { 
                return 'def'; 
            })
        ]);
    }

    /**
     * @Route("/set/{text}", name="constraint_set")
     */
    public function set(string $text): Response
    {
        $cache = new PdoAdapter($this->getDoctrine()->getConnection());
        $dupa = $cache->getItem('DUPA');
        $dupa->set($text);
        $cache->save($dupa);
        return $this->redirectToRoute('constraint_test');
    }
}
