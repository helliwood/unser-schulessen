<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210603120514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE action_plan_new (id INT UNSIGNED AUTO_INCREMENT NOT NULL, to_do_id INT UNSIGNED NOT NULL, created_by_id INT UNSIGNED DEFAULT NULL, what VARCHAR(1024) NOT NULL, how VARCHAR(1024) NOT NULL, who VARCHAR(1024) NOT NULL, `when` DATE NOT NULL, created_at DATETIME NOT NULL, completed TINYINT(1) DEFAULT NULL, closed TINYINT(1) DEFAULT \'0\' NOT NULL, note VARCHAR(1024) DEFAULT NULL, INDEX IDX_B0D58F7E5BE9ECD7 (to_do_id), INDEX IDX_B0D58F7EB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE to_do_new (id INT UNSIGNED AUTO_INCREMENT NOT NULL, school_id INT UNSIGNED NOT NULL, answer_id INT UNSIGNED DEFAULT NULL, created_by_id INT UNSIGNED DEFAULT NULL, closed_by_id INT UNSIGNED DEFAULT NULL, name VARCHAR(150) NOT NULL, description VARCHAR(2048) DEFAULT NULL, completed TINYINT(1) DEFAULT NULL, note VARCHAR(2048) DEFAULT NULL, created_at DATETIME NOT NULL, closed TINYINT(1) NOT NULL, closed_at DATETIME DEFAULT NULL, INDEX IDX_18DB824DC32A47EE (school_id), INDEX IDX_18DB824DAA334807 (answer_id), INDEX IDX_18DB824DB03A8386 (created_by_id), INDEX IDX_18DB824DE1FA7797 (closed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE action_plan_new ADD CONSTRAINT FK_B0D58F7E5BE9ECD7 FOREIGN KEY (to_do_id) REFERENCES to_do_new (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE action_plan_new ADD CONSTRAINT FK_B0D58F7EB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE to_do_new ADD CONSTRAINT FK_18DB824DC32A47EE FOREIGN KEY (school_id) REFERENCES school (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE to_do_new ADD CONSTRAINT FK_18DB824DAA334807 FOREIGN KEY (answer_id) REFERENCES answer (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE to_do_new ADD CONSTRAINT FK_18DB824DB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE to_do_new ADD CONSTRAINT FK_18DB824DE1FA7797 FOREIGN KEY (closed_by_id) REFERENCES user (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action_plan_new DROP FOREIGN KEY FK_B0D58F7E5BE9ECD7');
        $this->addSql('DROP TABLE action_plan_new');
        $this->addSql('DROP TABLE to_do_new');
    }
}
