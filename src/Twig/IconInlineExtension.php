<?php
// src/Twig/IconInlineExtension.php

namespace App\Twig;

use App\Service\IconHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IconInlineExtension extends AbstractExtension
{
    private IconHelper $helper;

    public function __construct(IconHelper $helper)
    {
        $this->helper = $helper;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('icon_inline', [$this->helper, 'iconInline'], ['is_safe' => ['html']]),
        ];
    }
}
