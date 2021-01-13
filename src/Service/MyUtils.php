<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\ConstraintRepository;
use Symfony\Component\Form\FormInterface;

class MyUtils
{
    public static function updateForm(
        FormInterface $form,
        string $childName,
        ?string $childType = null,
        array $options = []
    ) {
        $srcOptions = $form->get($childName)->getConfig()->getOptions();
        foreach ($options as $key => $val) {
            $srcOptions[$key] = $val;
        }
        $form->add($childName, $childType, $srcOptions);
    }

    public static function addOpeningHours(
        array &$headers,
        ConstraintRepository $cstrRepo,
        User $user
    ) {
        if (0 === count($headers)) {
            return;
        }
        $hours = $cstrRepo->getOpeningHours($user, reset($headers)['date'], end($headers)['date']);
        foreach ($headers as $key => $header) {
            $headers[$key]['hours'] = $hours[$header['date']->format('Y-m-d')];
        }
    }
}
