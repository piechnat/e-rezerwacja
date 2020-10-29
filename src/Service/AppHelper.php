<?php

namespace App\Service;

use App\CustomTypes\NotAllowedException;
use App\CustomTypes\ReservationConflictException;
use App\Entity\Reservation;
use App\CustomTypes\ReservationError as RsvnErr;
use Symfony\Component\Form\FormError;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class AppHelper
{
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

    public function createFormError(\Exception $exc): FormError
    {
        $content = '';
        if ($exc instanceof NotAllowedException) {
            $content .= $this->trans->trans(RsvnErr::getValue($exc->getMessage()));
        } elseif ($exc instanceof ReservationConflictException) {
            $content .= $this->trans->trans(RsvnErr::getValue(RsvnErr::RSVN_CONFLICT));
            $url = $this->generator->generate('reservation_show', ['id' => $exc->getMessage()]);
            $text = $this->trans->trans('Zobacz konflikt');
            $content .= ' <a href="'.$url.'">'.$text.'</a>.';
        }

        return new FormError($content);
    }

    public function isReservationAllowed(Reservation $rsvn): string
    {
        //return ReservationError::NO_PRIVILEGES;
        return RsvnErr::RSVN_ALLOWED;
    }
}
