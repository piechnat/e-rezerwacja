<?php

namespace App\Controller;

use App\CustomTypes\UserLevel;
use App\Entity\Request as EntityRequest;
use App\Entity\Reservation;
use App\Repository\RequestRepository;
use App\Service\AppMailer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/request")
 */
class RequestController extends AbstractController
{
    private const MAX_RQST_COUNT = 3;

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
    public function delete(EntityRequest $rqst, Request $request, AppMailer $mailer): Response
    {
        if (
            $this->canEditRequest($rqst)
            && $this->isCsrfTokenValid('request_delete', $request->request->get('token'))
        ) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($rqst);
            $em->flush();
            if ($this->getUser() !== $rqst->getRequester()) {
                $mailer->notify('Odrzucenie wniosku o rezerwację', 'Twój wniosek o rezerwację sali'.
                    ' %rqst_room% w dniu %rqst_date% został odrzucony przez %user%.', null, $rqst);
            }

            return $this->redirectToRoute('request_index');
        }

        throw $this->createAccessDeniedException();
    }

    public function add(
        Reservation $rsvn,
        string $rsvnError,
        RequestRepository $repo,
        AppMailer $mailer
    ): Response {
        if ($repo->getUserRequestsCount($rsvn->getEditor()) >= static::MAX_RQST_COUNT) {
            return $this->render('main/redirect.html.twig', [
                'path' => 'request_index',
                'main_content' => 'Przekroczono limit nierozpatrzonych wniosków o rezerwację sali.',
            ]);
        }
        $rqst = new EntityRequest();
        $rqst->setRequester($rsvn->getRequester());
        $rqst->setRoom($rsvn->getRoom());
        $rqst->setCreateTime($rsvn->getEditTime());
        $rqst->setBeginTime($rsvn->getBeginTime());
        $rqst->setEndTime($rsvn->getEndTime());
        $rqst->setDetails($rsvn->getDetails());
        $rqst->setError($rsvnError);
        
        $em = $this->getDoctrine()->getManager();
        if ($rsvn->getId()) {
            $rqst->setReservationId($rsvn->getId());
            $em->refresh($rsvn);
        }
        $em->persist($rqst);
        $em->flush();
        if ($this->getUser() !== $rqst->getRequester()) {
            $mailer->notify('Złożenie wniosku o rezerwację', '%user% złożył(a) w Twoim imieniu '.
                'wniosek o rezerwację sali %rqst_room% w dniu %rqst_date%.', null, $rqst);
        }

        return $this->redirectToRoute('request_index');
    }

    private function canEditRequest(EntityRequest $rqst): bool
    {
        if ($this->getUser() === $rqst->getRequester()) {
            return true;
        }

        return $this->isGranted(UserLevel::ADMIN);
    }
}
