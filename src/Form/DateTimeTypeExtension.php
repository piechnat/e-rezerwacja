<?php

namespace App\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeTypeExtension extends AbstractTypeExtension
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'date_attr' => [],
            'time_attr' => [],
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (count($options['date_attr']) > 0) {
            $dateOptions = $builder->get('date')->getOptions();
            $dateOptions['attr'] = $options['date_attr'];
            $builder->add('date', DateType::class, $dateOptions);
        }
        if (count($options['time_attr']) > 0) {
            $dateOptions = $builder->get('time')->getOptions();
            $timeOptions['attr'] = $options['time_attr'];
            $builder->add('time', TimeType::class, $timeOptions);
        }
    }

    public static function getExtendedTypes(): iterable
    {
        return [DateTimeType::class];
    }
}
