<?php

declare(strict_types=1);

namespace App\Twig;

use App\Menu\MenuBuilder;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MenuExtension extends AbstractExtension
{
    public function __construct(
        private readonly MenuBuilder $menuBuilder,
    ) {
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_main_menu', $this->getMainMenu(...)),
        ];
    }

    public function getMainMenu(): array
    {
        return $this->menuBuilder->buildMainMenu();
    }
}
