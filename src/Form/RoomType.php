<?php

namespace App\Form;

use App\Entity\Room;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['route_name'] === 'room_add') {
            $builder->add('titles', TextareaType::class, [
                'label' => 'Nazwy w kolejnych wierszach'
            ]);
        }
        if ($options['route_name'] === 'room_edit') {
            $builder
                ->add('title', TextType::class, ['label' => 'Nazwa sali'])
                ->add('tags', null, [
                    'by_reference' => false,
                    'label' => 'Etykiety',
                ]);
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
