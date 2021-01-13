<?php

namespace App\Form;

use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OpeningHoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new CallbackTransformer(
            function (?array $hours) {
                return $hours;
            },
            function (array $hours) {
                return isset($hours['from']) ? $hours : null;
            }
        );
        for ($i = 0; $i <= 7; ++$i) {
            $builder->add($i, OpeningHoursItemType::class);
            $builder->get($i)->addModelTransformer($transformer);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'error_bubbling' => false,
        ]);
    }
}

class OpeningHoursItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $tmpOpt = ['widget' => 'single_text', 'input' => 'datetime_immutable', 'required' => false];
        $builder->add('from', TimeType::class, array_merge($tmpOpt, ['label' => 'od']));
        $builder->add('to', TimeType::class, array_merge($tmpOpt, ['label' => 'do']));
        $transformer = new CallbackTransformer(
            function (?string $str) {
                return $str ? new DateTimeImmutable($str) : null;
            },
            function (?DateTimeInterface $time) {
                return $time ? $time->format('H:i') : null;
            }
        );
        $builder->get('from')->addModelTransformer($transformer);
        $builder->get('to')->addModelTransformer($transformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'error_bubbling' => true,
        ]);
    }
}
