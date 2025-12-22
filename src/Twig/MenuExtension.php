<?php

namespace App\Twig;

use App\Menu\MenuBuilder;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MenuExtension extends AbstractExtension
{
    private MenuBuilder $menuBuilder;

    public function __construct(MenuBuilder $menuBuilder)
    {
        $this->menuBuilder = $menuBuilder;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_main_menu', [$this, 'getMainMenu']),
        ];
    }

    public function getMainMenu(): array
    {
        return $this->menuBuilder->buildMainMenu();
    }
}
