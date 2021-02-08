<?php

namespace App\Service;

use App\Entity\Request;
use App\Entity\Reservation;
use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class AppMailer
{
    private $mailer;
    private $translator;
    private $generator;
    /** @var User */
    private $user;
    private $senderAddr;
    private $devRecipientAddr;
    private $appName;
    private $appSignature;

    public function __construct(
        MailerInterface $mailer,
        TranslatorInterface $translator,
        UrlGeneratorInterface $generator,
        Security $security,
        ContainerBagInterface $params
    ) {
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->generator = $generator;
        $this->user = $security->getUser();
        $this->senderAddr = $_ENV['MAILER_SENDER'];
        $this->devRecipientAddr = null;
        $this->appName = $params->get('app.name');
        $this->appSignature = $params->get('app.signature');

        if ('dev' === $_ENV['APP_ENV']) {
            $token = $security->getToken();
            if ($token instanceof SwitchUserToken) {
                $this->devRecipientAddr = $token->getOriginalToken()->getUser()->getUsername();
            } else {
                $this->devRecipientAddr = $this->user->getUsername();
            }
        }
    }

    public function send(string $email, string $subject, string $text)
    {
        if ($this->devRecipientAddr) {
            $subject .= " (should be sent to {$email})";
            $email = $this->devRecipientAddr;
        }
        $this->mailer->send(
            (new Email())
                ->from("{$this->appName} <{$this->senderAddr}>")
                ->to($email)
                ->subject($subject)
                ->text($text)
        );
    }

    public function notify(
        string $subject,
        string $text,
        Reservation $rsvn = null,
        Request $rqst = null,
        User $deliverTo = null
    ) {
        $recipient = $this->user;
        $params = ['%user%' => "‘{$this->user->getFullname()}’"];
        if ($rqst) {
            $params['%rqst_room%'] = "‘{$rqst->getRoom()->getTitle()}’";
            $params['%rqst_date%'] = '‘'.AppHelper::term($rqst).'’';
            $recipient = $rqst->getRequester();
        }
        if ($rsvn) {
            $params['%rsvn_room%'] = "‘{$rsvn->getRoom()->getTitle()}’";
            $params['%rsvn_date%'] = '‘'.AppHelper::term($rsvn).'’';
            if ($rsvn->getId()) {
                $rsvnUrl = $this->generator->generate(
                    'reservation_show',
                    ['id' => $rsvn->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $params['%rsvn_url%'] = "\r\n{$rsvnUrl}";
            }
            $recipient = $rsvn->getRequester();
        }
        if ($deliverTo) {
            $recipient = $deliverTo;
        }
        $siteUrl = $this->generator->generate('main', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $locale = $recipient->getLang();
        $trns = function (string $id, array $parameters = []) use ($locale) {
            return $this->translator->trans($id, $parameters, 'mailer', $locale);
        };
        $this->send(
            $recipient->getEmail(),
            $trns($subject),
            "{$trns('Szanowny Użytkowniku')},\r\n".
            "{$trns($text, $params)}\r\n".
            "--\r\n".
            "{$this->appName} » {$siteUrl}\r\n".
            "{$trns($this->appSignature)}"
        );
    }
}
