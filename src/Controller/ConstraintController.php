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
     * @Route("/{param}", name="constraint_index", requirements={"param"="clear"})
     */
    public function index(string $param = null, ConstraintRepository $cstrRepo): Response
    {
        if ('clear' === $param) {
            $cstrRepo->removeExpired();

            return $this->redirectToRoute('constraint_index');
        }

        return $this->render('constraint/index.html.twig', [
            'time_constraints' => $cstrRepo->findAll(),
        ]);
    }

    /**
     * @Route("/add", name="constraint_add")
     */
    public function add(Request $request): Response
    {
        $timeCstr = new TimeConstraint();
        $form = $this->createForm(TimeConstraintType::class, $timeCstr);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($timeCstr);
            $em->flush();

            return $this->redirectToRoute('constraint_index');
        }

        return $this->render('constraint/add.html.twig', [
            'time_cstr' => $timeCstr,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit/{id}", name="constraint_edit")
     */
    public function edit(Request $request, TimeConstraint $timeCstr): Response
    {
        $form = $this->createForm(TimeConstraintType::class, $timeCstr);
        $form->handleRequest($request);
        $willBeDeleted = 'delete' === $request->get('delete');

        if ($form->isSubmitted() && ($form->isValid() || $willBeDeleted)) {
            $em = $this->getDoctrine()->getManager();
            if ($willBeDeleted) {
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
