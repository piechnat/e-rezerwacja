<?php

namespace App\Controller;

use App\Entity\TimeConstraint;
use App\Form\TimeConstraintType;
use App\Repository\ConstraintRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * @Route("/constraint")
 */
class ConstraintController extends AbstractController
{
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
    public function reservation(Request $request, ConstraintRepository $cstrRepo): Response
    {
        $builder = $this->createFormBuilder();
        $rsvnLimits = $cstrRepo->getReservationLimits();
        foreach ($rsvnLimits as $key => $value) {
            $builder->add($key, IntegerType::class, [
                'label' => $key,
                'data' => $value,
                'constraints' => [
                    new Positive(),
                ],
            ]);
        }
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cstrRepo->setReservationLimits($form->getData());
        }

        return $this->render('constraint/reservation.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
