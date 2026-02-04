<?php

namespace App\Service\FlagDefinitions;

/**
 * Bayern-spezifische Flag-Definitionen
 */
class ByFlags
{
    /**
     * Flag-Definitionen für Bayern
     * @return array<string, array<string, string>>
     */
    public static function getFlagDefinitions(): array
    {
        return [
            'guidelineCheck' => [
                'description' => 'Leitlinien Check',
                'icon' => 'fas fa-thumbs-up',
                'color' => '#0079ac'
            ],
        ];
    }
}
