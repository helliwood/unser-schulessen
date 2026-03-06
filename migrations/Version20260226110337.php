<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260226110337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE survey_school_participation (id INT UNSIGNED AUTO_INCREMENT NOT NULL, survey_id INT UNSIGNED NOT NULL, school_id INT UNSIGNED NOT NULL, has_participated TINYINT(1) DEFAULT 0 NOT NULL, participated_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_B3E58A28B3FE509D (survey_id), INDEX IDX_B3E58A28C32A47EE (school_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE survey_school_participation ADD CONSTRAINT FK_B3E58A28B3FE509D FOREIGN KEY (survey_id) REFERENCES survey_survey (id)');
        $this->addSql('ALTER TABLE survey_school_participation ADD CONSTRAINT FK_B3E58A28C32A47EE FOREIGN KEY (school_id) REFERENCES school (id)');
        $this->addSql('ALTER TABLE survey_survey ADD school_authority_id INT UNSIGNED DEFAULT NULL, CHANGE school_id school_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE survey_survey ADD CONSTRAINT FK_855153904ADE33E5 FOREIGN KEY (school_authority_id) REFERENCES school_authority (id)');
        $this->addSql('CREATE INDEX IDX_855153904ADE33E5 ON survey_survey (school_authority_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE survey_school_participation DROP FOREIGN KEY FK_B3E58A28B3FE509D');
        $this->addSql('ALTER TABLE survey_school_participation DROP FOREIGN KEY FK_B3E58A28C32A47EE');
        $this->addSql('DROP TABLE survey_school_participation');
        $this->addSql('ALTER TABLE survey_survey DROP FOREIGN KEY FK_855153904ADE33E5');
        $this->addSql('DROP INDEX IDX_855153904ADE33E5 ON survey_survey');
        $this->addSql('ALTER TABLE survey_survey DROP school_authority_id, CHANGE school_id school_id INT UNSIGNED NOT NULL');
    }
}
