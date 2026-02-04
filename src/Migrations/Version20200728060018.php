<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200728060018 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE survey_surveyquestion_choice_answer (id INT UNSIGNED AUTO_INCREMENT NOT NULL, question_id INT UNSIGNED NOT NULL, voucher_id INT UNSIGNED DEFAULT NULL, choice_id INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL, user_agent VARCHAR(1024) DEFAULT NULL, user_ip VARCHAR(100) DEFAULT NULL, INDEX IDX_9128BF6E1E27F6BF (question_id), INDEX IDX_9128BF6E28AA1B6F (voucher_id), INDEX IDX_9128BF6E998666D1 (choice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE survey_surveyquestion_choice (id INT UNSIGNED AUTO_INCREMENT NOT NULL, question_id INT UNSIGNED NOT NULL, choice VARCHAR(255) NOT NULL, `order` SMALLINT NOT NULL, INDEX IDX_BB01022C1E27F6BF (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE survey_surveyquestion_choice_answer ADD CONSTRAINT FK_9128BF6E1E27F6BF FOREIGN KEY (question_id) REFERENCES survey_surveyquestion (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE survey_surveyquestion_choice_answer ADD CONSTRAINT FK_9128BF6E28AA1B6F FOREIGN KEY (voucher_id) REFERENCES survey_survey_voucher (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE survey_surveyquestion_choice_answer ADD CONSTRAINT FK_9128BF6E998666D1 FOREIGN KEY (choice_id) REFERENCES survey_surveyquestion_choice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE survey_surveyquestion_choice ADD CONSTRAINT FK_BB01022C1E27F6BF FOREIGN KEY (question_id) REFERENCES survey_surveyquestion (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE survey_surveyquestion ADD type VARCHAR(50) DEFAULT \'happy_unhappy\' NOT NULL, ADD `order` SMALLINT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE survey_surveyquestion_choice_answer DROP FOREIGN KEY FK_9128BF6E998666D1');
        $this->addSql('DROP TABLE survey_surveyquestion_choice_answer');
        $this->addSql('DROP TABLE survey_surveyquestion_choice');
        $this->addSql('ALTER TABLE survey_surveyquestion DROP type, DROP `order`');
    }
}
