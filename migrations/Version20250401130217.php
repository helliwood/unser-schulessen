<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250401130217 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mini_check_answer (id INT UNSIGNED AUTO_INCREMENT NOT NULL, question_id INT UNSIGNED NOT NULL, result_id INT UNSIGNED NOT NULL, answer VARCHAR(150) DEFAULT NULL, INDEX IDX_3B4A3CA61E27F6BF (question_id), INDEX IDX_3B4A3CA67A7B643 (result_id), UNIQUE INDEX UNIQ_3B4A3CA61E27F6BF7A7B643 (question_id, result_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mini_check_result (id INT UNSIGNED AUTO_INCREMENT NOT NULL, school_id INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_F2FDB790C32A47EE (school_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mini_check_answer ADD CONSTRAINT FK_3B4A3CA61E27F6BF FOREIGN KEY (question_id) REFERENCES question (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE mini_check_answer ADD CONSTRAINT FK_3B4A3CA67A7B643 FOREIGN KEY (result_id) REFERENCES mini_check_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mini_check_result ADD CONSTRAINT FK_F2FDB790C32A47EE FOREIGN KEY (school_id) REFERENCES school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE question ADD mini_check TINYINT(1) DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mini_check_answer DROP FOREIGN KEY FK_3B4A3CA67A7B643');
        $this->addSql('DROP TABLE mini_check_answer');
        $this->addSql('DROP TABLE mini_check_result');
        $this->addSql('ALTER TABLE question DROP mini_check');
    }
}
