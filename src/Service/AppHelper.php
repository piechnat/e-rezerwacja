<?php

namespace App\Service;

use App\Entity\Reservation;
use Exception;
use Symfony\Component\Form\FormError;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class AppHelper
{
    const RSVN_ALLOWED_MSG = 0;
    const RSVN_CONFLICT_MSG = 1;
    const NO_PRIVILEGES_MSG = 2;

    private const messageList = [
        self::RSVN_ALLOWED_MSG => 'Rezerwacja sali jest dozwolona.',
        self::RSVN_CONFLICT_MSG => 'W podanym terminie sala jest już zarezerwowana.',
        self::NO_PRIVILEGES_MSG => 'Nie posiadasz wystarczających uprawnień do zarezerwowania sali.',
    ];

    private $security;
    private $trans;
    private $generator;

    public function __construct(
        Security $security,
        TranslatorInterface $translator,
        UrlGeneratorInterface $generator
    ) {
        $this->security = $security;
        $this->trans = $translator;
        $this->generator = $generator;
    }

    public function createFormError(Exception $exception): FormError
    {
        $content = '';
        if ($exception instanceof NotAllowedException) {
            $content .= $this->trans->trans(self::messageList[$exception->getCode()]);
        } elseif ($exception instanceof ReservationConflictException) {
            $content .= $this->trans->trans(self::messageList[self::RSVN_CONFLICT_MSG]);
            $url = $this->generator->generate('reservation_show', ['id' => $exception->getCode()]);
            $text = $this->trans->trans('Zobacz konflikt');
            $content .= ' <a href="'.$url.'">'.$text.'</a>.';
        }

        return new FormError($content);
    }

    public function isReservationAllowed(Reservation $rsvn): int
    {
        return self::NO_PRIVILEGES_MSG;
        //return self::RSVN_ALLOWED_MSG;
    }
}
