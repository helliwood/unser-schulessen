<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230530140237 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO person_type SET name="Ernährungsberater"');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM person_type WHERE name="Ernährungsberater"');
    }
}
