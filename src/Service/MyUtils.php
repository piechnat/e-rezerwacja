<?php

namespace App\Service;

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
}