<?php

namespace App\Form;

use App\Entity\Reservation;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ReservationType extends AbstractType
{
    private $roomToTitle;
    private $userToEmail;
    private $em;

    public function __construct(
        RoomToTitleTransformer $roomToTitle,
        UserToEmailTransformer $userToEmail,
        EntityManagerInterface $em
    ) {
        $this->roomToTitle = $roomToTitle;
        $this->userToEmail = $userToEmail;
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['modify_requester']) {
            $builder
                ->add('username', TextType::class, [
                    'label' => 'Użytkownik',
                    'mapped' => false,
                    'required' => false,
                ])
                ->add('requester', TextType::class, [
                    'label' => 'E-mail',
                    'attr' => ['size' => 30],
                ])
            ;
        } else {
            $builder->add('requester', HiddenType::class);
        }
        $builder
            ->add('room', TextType::class, [
                'label' => 'Sala',
                'attr' => ['size' => 12],
            ])
            ->add('begin_time', DateTimeType::class, [
                'input' => 'datetime_immutable',
                'label' => 'Termin rozpoczęcia',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
            ])
            ->add('end_time', TimeType::class, [
                'input' => 'datetime_immutable',
                'label' => 'Godzina zakończenia',
                'widget' => 'single_text',
            ])
            ->add('details', TextareaType::class, ['label' => 'Cel rezerwacji'])
        ;
        if ($options['send_request']) {
            $builder->add('send_request', CheckboxType::class, [
                'mapped' => false,
                'label' => 'Złóż wniosek o rezerwację sali',
            ]);
        }

        $builder->get('requester')->addModelTransformer($this->userToEmail);
        $builder->get('room')->addModelTransformer($this->roomToTitle);

        if (false === $options['past_begin_time']) {
            $field = $builder->get('begin_time');

            $type = $field->get('date')->getType()->getInnerType();
            $options = $field->get('date')->getOptions();
            $attr = $options['attr'] ?? [];
            $attr['min'] = (new DateTimeImmutable())->format('Y-m-d');
            $options['attr'] = $attr;
            $field->add('date', get_class($type), $options);

            $field->addModelTransformer(new CallbackTransformer(
                function ($beginTime) {
                    return $beginTime;
                },
                function ($beginTime) {
                    return max(new DateTimeImmutable(), $beginTime);
                }
            ));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'constraints' => [
                new Assert\Callback([$this, 'validateReservation']),
            ],
            'allow_extra_fields' => true,
            'send_request' => false,
            'modify_requester' => false,
            'past_begin_time' => false,
        ]);
    }

    public function validateReservation(Reservation $rsvn, ExecutionContextInterface $context)
    {
        if (!$rsvn->getRoom() || !$rsvn->getRequester()) {
            // transformation failed therefore validation is unnecessary
            return;
        }

        $tmpTime = DateTime::createFromImmutable($rsvn->getBeginTime());
        $endTime = DateTime::createFromImmutable($rsvn->getEndTime());
        // change end time to a date on the same day as begin time
        $endTime->modify($tmpTime->format('Y-m-d'));
        if ($endTime <= $tmpTime) {
            $endTime->modify('+1 day'); // or next day if it is earlier
        }
        $rsvn->setEndTime(DateTimeImmutable::createFromMutable($endTime));

        $tmpTime->modify('+15 minutes');
        if ($endTime < $tmpTime) {
            $context->buildViolation('Rezerwacja nie może być krótsza niż 15 minut.')
                ->atPath('end_time')->addViolation();

            return;
        }

        $tmpTime->modify('+23 hours +45 minutes');
        if ($endTime > $tmpTime) {
            $context->buildViolation('Rezerwacja nie może być dłuższa niż 24 godziny.')
                ->atPath('end_time')->addViolation();

            return;
        }
    }
}
