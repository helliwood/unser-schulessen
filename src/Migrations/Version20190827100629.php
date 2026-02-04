<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190827100629 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE survey_survey (id INT UNSIGNED AUTO_INCREMENT NOT NULL, school_id INT UNSIGNED NOT NULL, created_by_id INT UNSIGNED NOT NULL, name VARCHAR(150) NOT NULL, type VARCHAR(50) NOT NULL, state SMALLINT DEFAULT 0 NOT NULL, closes_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_85515390C32A47EE (school_id), INDEX IDX_85515390B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE survey_surveyquestion (id INT UNSIGNED AUTO_INCREMENT NOT NULL, survey_id INT UNSIGNED NOT NULL, created_by_id INT UNSIGNED NOT NULL, question VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_359EEA25B3FE509D (survey_id), INDEX IDX_359EEA25B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE survey_survey ADD CONSTRAINT FK_85515390C32A47EE FOREIGN KEY (school_id) REFERENCES school (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE survey_survey ADD CONSTRAINT FK_85515390B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE survey_surveyquestion ADD CONSTRAINT FK_359EEA25B3FE509D FOREIGN KEY (survey_id) REFERENCES survey_survey (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE survey_surveyquestion ADD CONSTRAINT FK_359EEA25B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE to_do_item ADD completed TINYINT(1) DEFAULT NULL, ADD note VARCHAR(1024) DEFAULT NULL, CHANGE done closed TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE survey_surveyquestion DROP FOREIGN KEY FK_359EEA25B3FE509D');
        $this->addSql('DROP TABLE survey_survey');
        $this->addSql('DROP TABLE survey_surveyquestion');
        $this->addSql('ALTER TABLE to_do_item DROP completed, DROP note, CHANGE closed done TINYINT(1) NOT NULL');
    }
}
