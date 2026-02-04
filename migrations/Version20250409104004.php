<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250409104004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mini_check_result ADD questionnaire_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE mini_check_result ADD CONSTRAINT FK_F2FDB790CE07E8FF FOREIGN KEY (questionnaire_id) REFERENCES questionnaire (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_F2FDB790CE07E8FF ON mini_check_result (questionnaire_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mini_check_result DROP FOREIGN KEY FK_F2FDB790CE07E8FF');
        $this->addSql('DROP INDEX IDX_F2FDB790CE07E8FF ON mini_check_result');
        $this->addSql('ALTER TABLE mini_check_result DROP questionnaire_id');
    }
}
