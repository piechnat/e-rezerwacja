<?php

namespace App\Form;

use App\Entity\Room;
use App\Entity\User;
use App\Service\AppHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class RoomType extends AbstractType
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ('room_add' === $options['route_name']) {
            $builder->add('titles', TextareaType::class, [
                'label' => 'Nazwy w kolejnych wierszach',
            ]);
        }
        if ('room_edit' === $options['route_name']) {
            $selfLevel = AppHelper::USR($this->security)->getAccessLevel();
            $builder
                ->add('title', TextType::class, ['label' => 'Nazwa sali'])
                ->add('tags', null, [
                    'by_reference' => false,
                    'label' => 'Etykiety',
                    'choice_filter' => function ($tag) use ($selfLevel) {
                        return $tag->getLevel() < $selfLevel;
                    },
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Room::class,
            'validation_groups' => 'room',
            'route_name' => '',
        ]);
    }
}
