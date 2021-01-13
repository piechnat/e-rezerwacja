<?php

namespace App\Form;

use App\Entity\TimeConstraint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TimeConstraintType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, [
                'label' => 'Nazwa',
            ])
            ->add('begin_date', DateType::class, [
                'label' => 'Data początku',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('end_date', DateType::class, [
                'label' => 'Data końca',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
            ->add('schedule', OpeningHoursType::class, [
                'label' => 'Godziny otwarcia',
            ])
            ->add('exclusions', null, [
                'label' => 'Wyłączenia',
            ])
        ;
        $builder->get('schedule')->addModelTransformer(new CallbackTransformer(
            function (?array $days) {
                return $days;
            },
            function (array $days) {
                foreach ($days as $day) {
                    if (null !== $day) {
                        return $days;
                    }
                }

                return [];
            }
        ));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getData();
            if (!strlen($form['end_date'])) {
                $form['end_date'] = $form['begin_date'];
                $event->setData($form);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TimeConstraint::class,
            'constraints' => [new Callback([$this, 'validate'])],
        ]);
    }

    public function validate(TimeConstraint $timeCstr, ExecutionContextInterface $context)
    {
        if ($timeCstr->getBeginDate() > $timeCstr->getEndDate()) {
            $context->buildViolation('Data końca nie może być wcześniejsza niż początku.')
                ->atPath('end_date')->addViolation();
        }
    }
}
