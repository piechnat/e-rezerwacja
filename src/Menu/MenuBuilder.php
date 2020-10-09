<?php

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class MenuBuilder
{
    /** @var TranslatorInterface */
    private $trans;

    public function __construct(MenuFactory $factory, TranslatorInterface $translator)
    {
        $this->trans = $translator;
    }

    public function createMainMenu(
        RequestStack $requestStack,
        FactoryInterface $factory,
        Security $security,
        Environment $twig
    ) {
        $lang = 'pl' === $requestStack->getCurrentRequest()->cookies->get('lang', 'pl') ? 'en' : 'pl';
        $langButton = $twig->render('main/lang-'.$lang.'.html.twig');
        $menu = $factory->createItem('root');

        if (!$security->isGranted('ROLE_USER')) {
            $this->ac($menu, 'Strona główna', ['route' => 'main'], '<i class="fas fa-home"></i>');
            $this->ac($menu, 'Zaloguj', ['route' => 'login'], '<i class="fas fa-sign-in-alt"></i>');
            $this->ac($menu, 'lang-selector', ['route' => 'change_lang', 'routeParameters' => ['lang' => $lang], 'hide_label' => true], $langButton);
        } else {
            $child = $this->ac($menu, 'Witryna', ['uri' => '#'], '<i class="fas fa-globe"></i>');
            $this->ac($child, 'Strona główna', ['route' => 'main'], '<i class="fas fa-home"></i>');
            $this->ac($child, 'Wyloguj', ['route' => 'logout'], '<i class="fas fa-sign-out-alt"></i>');
            $this->ac($child, 'lang-selector', ['route' => 'change_lang', 'routeParameters' => ['lang' => $lang], 'hide_label' => true], $langButton);

            $child = $this->ac($menu, 'Użytkownicy', ['uri' => '#'], '<i class="fas fa-users"></i>');
            $this->ac($child, 'Pokaż', ['route' => 'user_index'], '<i class="far fa-eye"></i>');
            $this->ac($child, 'Mój profil', ['route' => 'user_self_show'], '<i class="far fa-user"></i>');

            if ($security->isGranted('ROLE_ADMIN')) {
                $child = $this->ac($menu, 'Sale', ['uri' => '#'], '<i class="fas fa-door-closed"></i>');
                $this->ac($child, 'Dodaj', ['route' => 'room_add'], '<i class="far fa-plus-square"></i>');
                $this->ac($child, 'Pokaż', ['route' => 'room_index'], '<i class="far fa-eye"></i>');
            } else {
                $child = $this->ac($menu, 'Sale', ['route' => 'room_index'], '<i class="fas fa-door-closed"></i>');
            }
            $child = $this->ac($menu, 'Rezerwacje', ['uri' => '#'], '<i class="fas fa-calendar"></i>');
            $this->ac($child, 'Dodaj', ['route' => 'reservation_add'], '<i class="far fa-calendar-plus"></i>');
            $this->ac($child, 'Pokaż', ['route' => 'reservation_index'], '<i class="far fa-eye"></i>');
            $this->ac($child, 'Moje', ['route' => 'reservation_index'], '<i class="far fa-check-square"></i>');
            if ($security->isGranted('ROLE_ADMIN')) {
                $this->ac($child, 'Żądania', ['route' => 'request'], '<i class="far fa-bell"></i>');
            }
        }

        return $menu;
    }

    private function ac(
        ItemInterface $item,
        string $name,
        array $options,
        string $html
    ): ItemInterface {
        if (!isset($options['hide_label']) || !$options['hide_label']) {
            $html .= $this->trans->trans($name);
        }
        $options['label'] = $html;

        return $item->addChild($name, $options)->setExtras([
            'safe_label' => true,
            'translation_domain' => false,
        ]);
    }
}
