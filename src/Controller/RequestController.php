<?php

namespace App\Controller;

use App\CustomTypes\UserLevel;
use App\Entity\Request as RsvnRequest;
use App\Entity\Reservation;
use App\Repository\RequestRepository;
use App\Service\AppMailer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/request")
 */
class RequestController extends AbstractController
{
    /**
     * @Route("/", name="request_index")
     */
    public function index(RequestRepository $rqstRepo): Response
    {
        $requests = [];
        if ($this->isGranted(UserLevel::ADMIN)) {
            $requests = $rqstRepo->findBy([], ['create_time' => 'ASC', 'room' => 'ASC']);
        } else {
            $requests = $rqstRepo->findBy(['requester' => $this->getUser()->getId()]);
        }

        return $this->render('request/index.html.twig', ['requests' => $requests]);
    }

    /**
     * @Route("/delete/{id}", name="request_delete")
     */
    public function delete(RsvnRequest $rqst, Request $request)
    {
        if (
            $this->isCsrfTokenValid('request_delete', $request->request->get('token'))
        ) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($rqst);
            $em->flush();

            return $this->redirectToRoute('request_index');
        }

        throw $this->createAccessDeniedException();
    }

    public function add(Reservation $rsvn): Response
    {
        $rqst = new RsvnRequest();
        $rqst->setRequester($rsvn->getRequester());
        $rqst->setRoom($rsvn->getRoom());
        $rqst->setCreateTime($rsvn->getEditTime());
        $rqst->setBeginTime($rsvn->getBeginTime());
        $rqst->setEndTime($rsvn->getEndTime());
        $rqst->setDetails($rsvn->getDetails());

        $em = $this->getDoctrine()->getManager();
        $em->persist($rqst);
        $em->flush();
        
        return $this->redirectToRoute('request_index');
    }
}
