<?php

namespace App\Service\FlagDefinitions;

/**
 * Bayern-spezifische Flag-Definitionen
 */
final class ByFlags
{
    /**
     * Flag-Definitionen für Bayern
     * @return array<string, array<string, string>>
     */
    public static function getFlagDefinitions(): array
    {
        return [
            'guidelineCheck' => [
                'color' => '#0079ac',
                'description' => 'Leitlinien Check',
                'icon' => 'fas fa-thumbs-up',
            ],
        ];
    }
}
