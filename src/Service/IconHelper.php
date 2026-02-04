<?php

namespace App\Service;

class IconHelper
{
    private string $svgDir;
    private string $pngCacheDir;

    public function __construct(string $svgDir, string $pngCacheDir)
    {
        $this->svgDir = \rtrim($svgDir, '/');
        $this->pngCacheDir = \rtrim($pngCacheDir, '/');
    }

    /**
     * Liefert Base64-PNG für PDF
     */
    public function getPdfIcon(string $iconClass, ?string $color = null, ?int $size = 10): string
    {
        // Mappe z. B. 'fas fa-leaf' auf FontAwesome-Pfaddatei
        $iconName = \str_replace('fas fa-', '', $iconClass);
        $svgPath = $this->svgDir . '/' . $iconName . '.svg';

        if (! \file_exists($svgPath)) {
            return ''; // oder ein Fallback
        }

        $svgContent = \file_get_contents($svgPath);

        // Farbe setzen
        if ($color) {
            // Entfernt vorhandenes fill, damit unsere Farbe greift
            $svgContent = \preg_replace('/fill="[^"]*"/', '', $svgContent);
            $svgContent = \str_replace('<svg ', '<svg fill="' . $color . '" ', $svgContent);
        }

        $base64 = \base64_encode($svgContent);

        return '<img src="data:image/svg+xml;base64,' . $base64 . '" style="width:' . $size . 'px;height:' . $size . 'px">';
    }
}
