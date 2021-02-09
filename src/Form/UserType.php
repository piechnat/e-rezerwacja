<?php

namespace App\Form;

use App\CustomTypes\UserLevel;
use App\Entity\User;
use App\Service\AppHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UserType extends AbstractType
{
    private $user = [];

    public function __construct(Security $security)
    {
        $user = AppHelper::USR($security);
        $this->user['self'] = ['id' => $user->getId(), 'level' => $user->getAccessLevel()];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User */
        $user = $builder->getData();
        $this->user['origin'] = ['id' => $user->getId(), 'level' => $user->getAccessLevel()];

        $builder->add('fullname', TextType::class, [
            'label' => 'Nazwa wyświetlana',
            'constraints' => new Length(['min' => 3]),
        ]);
        if ($options['admin_edit']) {
            $selfLevel = $this->user['self']['level'];
            $builder->add('access', ChoiceType::class, [
                'choices' => $options['access_names'],
                'label' => 'Typ konta',
                'expanded' => false,
                'multiple' => false,
            ])->add('tags', null, [
                'by_reference' => false,
                'label' => 'Etykiety',
                'choice_filter' => function ($tag) use ($selfLevel) {
                    return $tag->getLevel() < $selfLevel;
                },
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'admin_edit' => false,
            'access_names' => [],
            'constraints' => [new Assert\Callback([$this, 'validateUserLevel'])],
        ]);
    }

    public function validateUserLevel(User $modifiedUser, ExecutionContextInterface $context)
    {
        if ($this->user['self']['id'] !== $this->user['origin']['id']) {
            if (
                $this->user['self']['level'] < UserLevel::getIndex(UserLevel::ADMIN) ||
                $this->user['self']['level'] < $this->user['origin']['level']
            ) {
                return $context->buildViolation('Nie masz uprawnień do edycji tego użytkownika.')
                    ->addViolation();
            }
        }
        if ($this->user['self']['level'] < $modifiedUser->getAccessLevel()) {
            $context->buildViolation('Nie możesz ustawić tej wartości.')
                ->atPath('access')->addViolation();
        }
    }
}
