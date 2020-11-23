<?php

namespace App\Menu;

use App\CustomTypes\Lang;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use App\CustomTypes\UserLevel;

class MenuBuilder
{
    /** @var TranslatorInterface */
    private $trans;

    public function __construct(MenuFactory $factory, TranslatorInterface $translator)
    {
        $this->trans = $translator;
    }

    public function createMainMenu(
        RequestStack $rStack,
        FactoryInterface $factory,
        Security $security,
        Environment $twig
    ) {
        /** @var User */
        $user = $security->getUser();
        $lang = $user ? $user->getLang() : Lang::fromCookie($rStack->getCurrentRequest()->cookies);
        $lang = $lang === Lang::PL ? Lang::EN : Lang::PL;
        $langButton = $twig->render('main/lang-'. $lang .'.html.twig');
        $menu = $factory->createItem('root');

        if (!$security->isGranted(UserLevel::USER)) {
            $this->ac($menu, 'Zaloguj', ['route' => 'login'], '<i class="fas fa-sign-in-alt"></i>');
            $this->ac($menu, 'lang-selector', ['route' => 'change_lang', 'routeParameters' => ['lang' => $lang], 'hide_label' => true], $langButton);
        } else {
            $child = $this->ac($menu, 'Użytkownik', ['uri' => '#'], '<i class="fas fa-user"></i>');
            $this->ac($child, 'Profil', ['route' => 'user_self_show'], '<i class="far fa-address-card"></i>');
            $this->ac($child, 'Rezerwacje', ['route' => 'reservation_view_user'], '<i class="far fa-eye"></i>');
            $this->ac($child, 'lang-selector', ['route' => 'change_lang', 'routeParameters' => ['lang' => $lang], 'hide_label' => true], $langButton);
            $this->ac($child, 'Wyloguj', ['route' => 'logout'], '<i class="fas fa-sign-out-alt"></i>');

            $child = $this->ac($menu, 'Sala', ['uri' => '#'], '<i class="fas fa-door-closed"></i>');
            $this->ac($child, 'Indeks', ['route' => 'room_index'], '<i class="far fa-list-alt"></i>');
            $this->ac($child, 'Kalendarz', ['route' => 'reservation_view_week'], '<i class="far fa-eye"></i>');
            if ($security->isGranted(UserLevel::ADMIN)) {
                $this->ac($child, 'Dodaj', ['route' => 'room_add'], '<i class="far fa-plus-square"></i>');
                $this->ac($child, 'Tagi', ['route' => 'tag_index'], '<i class="fas fa-tags"></i>');
            }

            $child = $this->ac($menu, 'Rezerwacje', ['uri' => '#'], '<i class="fas fa-calendar"></i>');
            $this->ac($child, 'Dodaj', ['route' => 'reservation_add'], '<i class="far fa-calendar-plus"></i>');
            $this->ac($child, 'Wykaz dzienny', ['route' => 'reservation_view_day'], '<i class="far fa-eye"></i>');
            if ($security->isGranted(UserLevel::ADMIN)) {
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
