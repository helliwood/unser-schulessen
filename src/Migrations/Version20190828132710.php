<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190828132710 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE survey_surveyquestion DROP FOREIGN KEY FK_359EEA25B03A8386');
        $this->addSql('DROP INDEX IDX_359EEA25B03A8386 ON survey_surveyquestion');
        $this->addSql('ALTER TABLE survey_surveyquestion DROP created_by_id, DROP created_at');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE survey_surveyquestion ADD created_by_id INT UNSIGNED NOT NULL, ADD created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE survey_surveyquestion ADD CONSTRAINT FK_359EEA25B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_359EEA25B03A8386 ON survey_surveyquestion (created_by_id)');
    }
}
