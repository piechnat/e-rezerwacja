<?php

namespace App\Service;

use App\Entity\Request;
use App\Entity\Reservation;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Security;

class AppMailer
{
    private $mailer;
    private $senderAddr;
    private $devRecipientAddr;

    public function __construct(MailerInterface $mailer, Security $security)
    {
        $this->mailer = $mailer;
        $this->senderAddr = $_ENV['MAILER_SENDER'];

        if ('dev' === $_ENV['APP_ENV']) {
            $token = $security->getToken();
            if ($token instanceof SwitchUserToken) {
                $this->devRecipientAddr = $token->getOriginalToken()->getUser()->getUsername();
            } else {
                $this->devRecipientAddr = $security->getUser()->getUsername();
            }
        } else {
            $this->devRecipientAddr = null;
        }
    }

    public function sendRequestAdded(Request $rqst)
    {
        $this->mailer->send((new Email())
            ->from($this->senderAddr)
            ->to($this->devRecipientAddr ?? $rqst->getRequester()->getEmail())
            ->subject('Wniosek o rezerwację sali')
            ->text('treść bla bla zażółć gęślą jaźń')
        );
    }

    public function sendRequestRejected(Request $rqst)
    {
    }

    public function sendReservationAdded(Reservation $rsvn)
    {
    }
}
