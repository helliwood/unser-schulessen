<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Überführt sustainable-Daten in das neue Flags-System
 */
final class Version20250717145031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Überführt sustainable-Daten in das neue Flags-System (flags JSON)';
    }

    public function up(Schema $schema): void
    {
        // Alle Fragen mit sustainable=1 und (keine oder leere Flags)
        $this->addSql(
            "UPDATE question SET flags = JSON_OBJECT('sustainable', true) WHERE sustainable = 1 AND (flags IS NULL OR flags = '')"
        );

        // Alle Fragen mit sustainable=1 und bestehenden Flags
        $questions = $this->connection->fetchAllAssociative(
            "SELECT id, flags FROM question WHERE sustainable = 1 AND flags IS NOT NULL AND flags != ''"
        );
        foreach ($questions as $question) {
            $flags = json_decode($question['flags'], true) ?: [];
            $flags['sustainable'] = true;
            $this->addSql(
                "UPDATE question SET flags = '" . json_encode($flags) . "' WHERE id = " . (int)$question['id']
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Entfernt das sustainable-Flag aus allen Fragen
        $questions = $this->connection->fetchAllAssociative(
            "SELECT id, flags FROM question WHERE flags LIKE '%sustainable%'"
        );
        foreach ($questions as $question) {
            $flags = json_decode($question['flags'], true) ?: [];
            if (isset($flags['sustainable'])) {
                unset($flags['sustainable']);
                $newFlags = empty($flags) ? null : json_encode($flags);
                $this->addSql(
                    "UPDATE question SET flags = " . ($newFlags === null ? 'NULL' : "'" . $newFlags . "'") . " WHERE id = " . (int)$question['id']
                );
            }
        }
    }
} 