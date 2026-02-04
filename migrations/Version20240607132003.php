<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240607132003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE food_survey (id INT UNSIGNED AUTO_INCREMENT NOT NULL, school_id INT UNSIGNED NOT NULL, created_by_id INT UNSIGNED NOT NULL, uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(150) NOT NULL, state SMALLINT DEFAULT 0 NOT NULL, activated_at DATETIME DEFAULT NULL, closes_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_ED71F15FD17F50A6 (uuid), INDEX IDX_ED71F15FC32A47EE (school_id), INDEX IDX_ED71F15FB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE food_survey_result (id INT UNSIGNED AUTO_INCREMENT NOT NULL, food_survey_id INT UNSIGNED NOT NULL, user_agent VARCHAR(1024) DEFAULT NULL, user_ip VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_1AD25E82B043D06 (food_survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE food_survey_spot (id INT UNSIGNED AUTO_INCREMENT NOT NULL, food_survey_id INT UNSIGNED NOT NULL, name VARCHAR(150) DEFAULT NULL, data LONGTEXT DEFAULT NULL, `order` SMALLINT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_DE6DAC762B043D06 (food_survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE food_survey_spot_answer (id INT UNSIGNED AUTO_INCREMENT NOT NULL, food_survey_result_id INT UNSIGNED NOT NULL, food_survey_spot_id INT UNSIGNED NOT NULL, answer SMALLINT NOT NULL, INDEX IDX_65A1E47481244B9B (food_survey_result_id), INDEX IDX_65A1E47478C3F0C (food_survey_spot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE food_survey ADD CONSTRAINT FK_ED71F15FC32A47EE FOREIGN KEY (school_id) REFERENCES school (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE food_survey ADD CONSTRAINT FK_ED71F15FB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE food_survey_result ADD CONSTRAINT FK_1AD25E82B043D06 FOREIGN KEY (food_survey_id) REFERENCES food_survey (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE food_survey_spot ADD CONSTRAINT FK_DE6DAC762B043D06 FOREIGN KEY (food_survey_id) REFERENCES food_survey (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE food_survey_spot_answer ADD CONSTRAINT FK_65A1E47481244B9B FOREIGN KEY (food_survey_result_id) REFERENCES food_survey_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE food_survey_spot_answer ADD CONSTRAINT FK_65A1E47478C3F0C FOREIGN KEY (food_survey_spot_id) REFERENCES food_survey_spot (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE food_survey_result DROP FOREIGN KEY FK_1AD25E82B043D06');
        $this->addSql('ALTER TABLE food_survey_spot DROP FOREIGN KEY FK_DE6DAC762B043D06');
        $this->addSql('ALTER TABLE food_survey_spot_answer DROP FOREIGN KEY FK_65A1E47481244B9B');
        $this->addSql('ALTER TABLE food_survey_spot_answer DROP FOREIGN KEY FK_65A1E47478C3F0C');
        $this->addSql('DROP TABLE food_survey');
        $this->addSql('DROP TABLE food_survey_result');
        $this->addSql('DROP TABLE food_survey_spot');
        $this->addSql('DROP TABLE food_survey_spot_answer');
    }
}
