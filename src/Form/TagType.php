<?php

namespace App\Form;

use App\CustomTypes\UserLevel;
use App\Entity\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ('entity' === $options['edit_mode']) {
            $choices = array_flip(array_values(array_slice(UserLevel::getValues(), 0, -1)));
            $builder
                ->add('name', null, [
                    'label' => 'Nazwa',
                    'attr' => ['maxlength' => 32],
                ])
                ->add('search', null, ['label' => 'Funkcja wyszukiwania'])
                ->add('level', ChoiceType::class, [
                    'choices' => $choices,
                    'label' => 'Ograniczenie dostępu do poziomu',
                    //'attr' => ['class' => 'jqslct2-single-select'],
                ])
            ;
        }
        if ('rooms' === $options['edit_mode']) {
            $builder->add('rooms', null, [
                'label' => 'Przyporządkowane sale',
                'attr' => ['class' => 'jqslct2-multiple-select'],
            ]);
        }
        if ('users' === $options['edit_mode']) {
            $builder->add('users', null, [
                'choice_label' => 'title',
                'label' => 'Przyporządkowani użytkownicy',
                'attr' => ['class' => 'jqslct2-multiple-select'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Tag::class,
            'validation_groups' => 'tag',
            'edit_mode' => null,
        ]);
    }
}
