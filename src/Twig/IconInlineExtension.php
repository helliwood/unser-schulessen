<?php
// src/Twig/IconInlineExtension.php

namespace App\Twig;

use App\Service\IconHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class IconInlineExtension extends AbstractExtension
{
    public function __construct(private IconHelper $helper)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('icon_inline', [$this->helper, 'iconInline'], ['is_safe' => ['html']]),
        ];
    }
}
