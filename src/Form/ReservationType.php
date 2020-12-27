<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Service\MyUtils;
use DateTimeImmutable;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ReservationType extends AbstractType
{
    private $roomToTitle;
    private $userToEmail;
    private $security;

    public function __construct(
        RoomToTitleTransformer $roomToTitle,
        UserToEmailTransformer $userToEmail,
        Security $security
    ) {
        $this->roomToTitle = $roomToTitle;
        $this->userToEmail = $userToEmail;
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $rsvn = $builder->getData();
        if (null === $rsvn) {
            $rsvn = new Reservation();
            $builder->setData($rsvn);
        }
        // PRE_SET_DATA
        $now = new DateTimeImmutable();
        $beginTime = $rsvn->getBeginTime();
        $endTime = $rsvn->getEndTime();
        if (null === $beginTime) {
            $beginTime = $now;
            $rsvn->setBeginTime($beginTime);
        }
        if ($endTime < $now && false === $options['past_time_edit']) {
            $endTime = $beginTime->modify('+60 minutes');
            $rsvn->setEndTime($endTime);
        }

        if ($options['modify_requester']) {
            $user = $rsvn->getRequester();
            $fullname = $user ? $user->getFullname() : null;
            $builder
                ->add('requester', TextType::class, [
                    'label' => 'Użytkownik',
                    'attr' => ['data-text' => $fullname],
                ])
                ->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($user) {
                    // modify data-text attribute if the requester has changed
                    $newUser = $event->getData()->getRequester();
                    if ($user !== $newUser) {
                        MyUtils::updateForm($event->getForm(), 'requester', TextType::class, [
                            'attr' => ['data-text' => $newUser ? $newUser->getFullname() : null],
                        ]);
                    }
                })
                ->get('requester')->addModelTransformer($this->userToEmail)
            ;
        } else {
            if (null === $rsvn->getRequester()) {
                $rsvn->setRequester($this->security->getUser());
            }
        }
        $builder
            ->add('room', TextType::class, [
                'label' => 'Sala',
            ])
            ->add('begin_time', DateTimeType::class, [
                'input' => 'datetime_immutable',
                'label' => 'Termin rozpoczęcia',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'date_attr' => ['min' => $now->format('Y-m-d')],
            ])
            ->add('end_time', TimeType::class, [
                'input' => 'datetime_immutable',
                'label' => 'Godzina zakończenia',
                'widget' => 'single_text',
            ])
            ->add('details', TextareaType::class, [
                'required' => false,
                'label' => 'Cel rezerwacji',
            ])
            ->get('room')->addModelTransformer($this->roomToTitle);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($options) {
            $rsvn = $event->getData();
            $beginTime = $rsvn->getBeginTime();
            if (false === $options['past_time_edit']) {
                $now = new DateTimeImmutable();
                if ($beginTime < $now) {
                    $rsvn->setBeginTime($now);
                    MyUtils::updateForm($event->getForm(), 'begin_time', DateTimeType::class);
                    $beginTime = $now;
                }
            }
            // change end_time to a date on the same day as begin_time
            $endTime = $rsvn->getEndTime();
            $endTime = $endTime->modify($beginTime->format('Y-m-d'));
            if ($endTime < $beginTime) {
                $endTime = $endTime->modify('+1 day'); // or next day if it is earlier
            }
            $rsvn->setEndTime($endTime);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'constraints' => [new Callback([$this, 'validateReservation'])],
            'allow_extra_fields' => true,
            'modify_requester' => false,
            'past_time_edit' => false,
        ]);
    }

    public function validateReservation(Reservation $rsvn, ExecutionContextInterface $context)
    {
        if ($rsvn->getEndTime() < $rsvn->getBeginTime()->modify('+45 minutes')) {
            $context->buildViolation('Rezerwacja nie może być krótsza niż 45 minut.')
                ->atPath('end_time')->addViolation();
        }
    }
}
