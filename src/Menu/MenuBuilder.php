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
        $isSuperAdmin = $security->isGranted(UserLevel::SUPER_ADMIN);

        if (!$security->isGranted(UserLevel::USER)) {

            $this->ac($menu, 'Zaloguj', ['route' => 'login'], '<i class="bx bx-log-in"></i>');
            $this->ac($menu, 'lang-selector', ['route' => 'change_lang', 'routeParameters' => ['lang' => $lang], 'hide_label' => true], $langButton);

        } else {

            $child = $this->ac($menu, 'Użytkownik', ['uri' => '#'], '<i class="bx bxs-user"></i>');
            $this->ac($child, 'Profil', ['route' => 'user_self_show'], '<i class="bx bx-id-card"></i>');
            $this->ac($child, 'Rezerwacje', ['route' => 'reservation_view_user'], '<i class="bx bx-calendar-week"></i>');
            $this->ac($child, 'lang-selector', ['route' => 'change_lang', 'routeParameters' => ['lang' => $lang], 'hide_label' => true], $langButton);
            $this->ac($child, 'Wyloguj', ['route' => 'logout'], '<i class="bx bx-log-out"></i>');

            $child = $this->ac($menu, 'Sala', ['uri' => '#'], '<i class="bx bxs-door-open"></i>');
            if ($isSuperAdmin) {
                $this->ac($child, 'Dodaj', ['route' => 'room_add'], '<i class="bx bx-add-to-queue"></i>');
            }
            $this->ac($child, 'Katalog', ['route' => 'room_form_show'], '<i class="bx bx-folder-open"></i>');
            $this->ac($child, 'Rezerwacje', ['route' => 'reservation_view_week'], '<i class="bx bx-calendar-week"></i>');
            if ($isSuperAdmin) {
                $this->ac($child, 'Etykiety', ['route' => 'tag_index'], '<i class="bx bx-purchase-tag"></i>');
            }

            $child = $this->ac($menu, 'Rezerwacja', ['uri' => '#'], '<i class="bx bxs-calendar"></i>');
            $this->ac($child, 'Dodaj', ['route' => 'reservation_add'], '<i class="bx bx-calendar-plus"></i>');
            $this->ac($child, 'Wykaz dzienny', ['route' => 'reservation_view_day'], '<i class="bx bx-calendar-week"></i>');
            if ($security->isGranted(UserLevel::ADMIN)) {
                $this->ac($child, 'Żądania', ['route' => 'requests'], '<i class="bx bx-bell"></i>');
            }
            if ($isSuperAdmin) {
                $this->ac($child, 'Ograniczenia', ['route' => 'constraints'], '<i class="bx bx-time"></i>');
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
