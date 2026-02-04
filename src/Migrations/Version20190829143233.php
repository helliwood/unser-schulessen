<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190829143233 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE survey_survey_voucher (id INT UNSIGNED AUTO_INCREMENT NOT NULL, survey_id INT UNSIGNED NOT NULL, created_by_id INT UNSIGNED NOT NULL, voucher VARCHAR(150) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_E4B3E7CEB3FE509D (survey_id), INDEX IDX_E4B3E7CEB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE survey_surveyquestion_answer (id INT UNSIGNED AUTO_INCREMENT NOT NULL, question_id INT UNSIGNED NOT NULL, voucher_id INT UNSIGNED DEFAULT NULL, created_at DATETIME NOT NULL, answer TINYINT(1) DEFAULT NULL, user_agent VARCHAR(1024) DEFAULT NULL, user_ip VARCHAR(100) DEFAULT NULL, INDEX IDX_A077129B1E27F6BF (question_id), INDEX IDX_A077129B28AA1B6F (voucher_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE survey_survey_voucher ADD CONSTRAINT FK_E4B3E7CEB3FE509D FOREIGN KEY (survey_id) REFERENCES survey_survey (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE survey_survey_voucher ADD CONSTRAINT FK_E4B3E7CEB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE survey_surveyquestion_answer ADD CONSTRAINT FK_A077129B1E27F6BF FOREIGN KEY (question_id) REFERENCES survey_surveyquestion (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE survey_surveyquestion_answer ADD CONSTRAINT FK_A077129B28AA1B6F FOREIGN KEY (voucher_id) REFERENCES survey_survey_voucher (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE survey_survey ADD activated_at DATETIME DEFAULT NULL, ADD number_of_participants INT UNSIGNED NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE survey_surveyquestion_answer DROP FOREIGN KEY FK_A077129B28AA1B6F');
        $this->addSql('DROP TABLE survey_survey_voucher');
        $this->addSql('DROP TABLE survey_surveyquestion_answer');
        $this->addSql('ALTER TABLE survey_survey DROP activated_at, DROP number_of_participants');
    }
}
