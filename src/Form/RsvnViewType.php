<?php

namespace App\Form;

use App\Entity\Tag;
use App\Repository\TagRepository;
use DateTime;
use DateTimeImmutable;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RsvnViewType extends AbstractType
{
    private $roomToTitle;
    private $userToEmail;
    private $generator;

    public function __construct(
        RoomToTitleTransformer $roomToTitle, 
        UserToEmailTransformer $userToEmail,
        UrlGeneratorInterface $generator
    ) {
        $this->roomToTitle = $roomToTitle;
        $this->userToEmail = $userToEmail;
        $this->generator = $generator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAction($this->generator->generate($options['route_name']))->setMethod('GET');

        if ($options['route_name'] === 'reservation_view_week') {
            $builder->add('room', TextType::class, [
                'data' => $options['room'],
                'data_class' => null,
                'label' => 'Sala',
            ])
            ->get('room')->addModelTransformer($this->roomToTitle);
        }
        if ($options['route_name'] === 'reservation_view_user') {
            $builder->add('user', TextType::class, [
                'data' => $options['user'],
                'data_class' => null,
                'label' => 'Użytkownik',
            ])
            ->get('user')->addModelTransformer($this->userToEmail);
        }
        if ($options['route_name'] === 'reservation_view_day') {
            $builder->add('tags', EntityType::class, [
                'label' => 'Pokaż sale posiadające etykiety',
                'class' => Tag::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'choices' => $options['tags'],
            ])
            ->add('tag_intersect', ChoiceType::class, [
                'label' => 'Etykiety',
                'choices' => ['Wszystkie' => 1, 'Dowolne' => 0],
                'expanded' => false,
                'multiple' => false,
            ]);
        }
        $builder->add('date', DateType::class, [
            'label' => 'Dzień',
            'input' => 'datetime_immutable',
            'widget' => 'single_text',
            'data' => $options['date'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'route_name' => 'reservation_view_week',
            'user' => null,
            'room' => null,
            'date' => null,
            'tags' => [],
        ]);
    }
}
