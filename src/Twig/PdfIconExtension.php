<?php

namespace App\Twig;

use App\Service\IconHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PdfIconExtension extends AbstractExtension
{
    private IconHelper $helper;

    public function __construct(IconHelper $helper)
    {
        $this->helper = $helper;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pdf_icon', [$this, 'pdfIcon'], ['is_safe' => ['html']]),
        ];
    }

    public function pdfIcon(string $name, string $color, ?int $size = 10): string
    {
        return $this->helper->getPdfIcon($name, $color, $size);
    }
}
