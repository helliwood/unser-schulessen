<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Entferne miniCheck aus flags JSON und behandle es als separates Attribut
 */
final class Version20250731120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Entferne miniCheck aus flags JSON und behandle es als separates Attribut';
    }

    public function up(Schema $schema): void
    {
        // Entferne miniCheck aus dem flags JSON für alle Questions
        $this->addSql("
            UPDATE question 
            SET flags = JSON_REMOVE(flags, '$.miniCheck')
            WHERE JSON_EXTRACT(flags, '$.miniCheck') IS NOT NULL
        ");
    }

    public function down(Schema $schema): void
    {
        // Migration kann nicht rückgängig gemacht werden, da miniCheck jetzt ein separates Attribut ist
        // Eine Rückwärts-Migration würde die Datenintegrität gefährden
        $this->addSql("-- Migration kann nicht rückgängig gemacht werden");
    }
} 