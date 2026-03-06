<?php

namespace App\Twig;

use App\Service\IconHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PdfIconExtension extends AbstractExtension
{
    public function __construct(private IconHelper $helper)
    {
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
