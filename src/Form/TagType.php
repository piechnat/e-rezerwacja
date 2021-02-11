<?php

namespace App\Form;

use App\CustomTypes\UserLevel;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagType extends AbstractType
{
    private $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ('entity' === $options['edit_mode']) {
            $choices = array_flip(array_values(array_slice(UserLevel::getValues(), 0, -1)));
            $builder
                ->add('name', null, ['label' => 'Nazwa'])
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
            $modifyForm = function ($form, $users) {
                $form->add('users', EntityType::class, [
                    'class' => User::class,
                    'multiple' => true,
                    'expanded' => false,
                    'choices' => $users,
                    'choice_label' => function (User $user) {
                        $username = strstr($user->getEmail(), '@', true);
                        return "{$user->getFullname()} ({$username})";
                    },
                    'required' => false,
                    'label' => 'Przyporządkowani użytkownicy',
                ]);
            };
            $builder->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($modifyForm) {
                    $modifyForm($event->getForm(), $event->getData()->getUsers());
                }
            );
            $userRepo = $this->userRepo;
            $builder->addEventListener(
                FormEvents::PRE_SUBMIT,
                function (FormEvent $event) use ($modifyForm, $userRepo) {
                    $userIds = $event->getData()['users'] ?? null;
                    $users = $userIds ? $userRepo->createQueryBuilder('user')
                        ->where('user.id IN (:userIds)')->setParameter('userIds', $userIds)
                        ->getQuery()->getResult() : [];
                    $modifyForm($event->getForm(), $users);
                }
            );
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
