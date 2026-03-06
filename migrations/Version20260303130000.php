<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add soft-delete flags for surveys and food surveys';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE survey_survey ADD deleted TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE food_survey ADD deleted TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE survey_survey DROP deleted');
        $this->addSql('ALTER TABLE food_survey DROP deleted');
    }
}
