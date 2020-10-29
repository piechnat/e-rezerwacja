<?php

namespace App\Form;

use App\Entity\Tag;
use DateTimeImmutable;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReservationViewType extends AbstractType
{
    private $roomToTitle;
    private $generator;

    public function __construct(
        RoomToTitleTransformer $roomToTitle, 
        UrlGeneratorInterface $generator
    ) {
        $this->roomToTitle = $roomToTitle;
        $this->generator = $generator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
        $builder->setAction($this->generator->generate($options['route_name']))
            ->setMethod('GET')
            ->add('date', DateType::class, [
                'label' => 'Dzień',
                'input' => 'datetime_immutable',
                'widget' => 'single_text',
                'empty_data' => $options['date']->format('Y-m-d'),
                'data' => $options['date'],
            ])
        ;
        if ($options['route_name'] === 'reservation_view_week') {
            $builder->add('room', TextType::class, [
                'label' => 'Sala',
                'attr' => ['size' => 12],
            ])
            ->get('room')->addModelTransformer($this->roomToTitle);
        }
        if ($options['route_name'] === 'reservation_view_day') {
            $builder->add('tags', EntityType::class, [
                'label' => 'Znaczniki sal',
                'class' => Tag::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('operation', ChoiceType::class, [
                'label' => 'Typ zbioru',
                'choices' => ['Suma' => 0, 'Iloczyn' => 1],
                'expanded' => false,
                'multiple' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'route_name' => 'reservation_view_week',
            'date' => new DateTimeImmutable(),
            'csrf_protection' => false,
        ]);
    }
}
