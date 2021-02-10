<?php

namespace App\Form;

use App\CustomTypes\UserLevel;
use App\Entity\Tag;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Tag */
        $tag = $builder->getData();
        if ('entity' === $options['edit_mode']) {
            $choices = array_flip(array_values(array_slice(UserLevel::getValues(), 0, -1)));
            $builder
                ->add('name', null, [
                    'label' => 'Nazwa',
                ])
                ->add('search', null, ['label' => 'Funkcja wyszukiwania'])
                ->add('level', ChoiceType::class, [
                    'choices' => $choices,
                    'label' => 'Ograniczenie dostępu do poziomu',
                ])
            ;
        }
        if ('rooms' === $options['edit_mode']) {
            $builder->add('rooms', null, [
                'label' => 'Przyporządkowane sale',
            ]);
        }
        if ('users' === $options['edit_mode']) {
            $builder->add('ajax_users', ChoiceType::class, [
                'mapped' => false,
                'multiple' => true,
                'expanded' => false,
                'choices' => $options['ajax_users'],
                'choice_value' => 'id',
                'choice_label' => function (User $user) {
                    $username = strstr($user->getEmail(), '@', true);

                    return "{$user->getFullname()} ({$username})";
                },
                'choice_attr' => function () {
                    return ['selected' => true];
                },
                'required' => false,
                'label' => 'Przyporządkowani użytkownicy',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Tag::class,
            'validation_groups' => 'tag',
            'edit_mode' => null,
            'ajax_users' => [],
        ]);
    }
}
