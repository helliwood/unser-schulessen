<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190806090728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('TRUNCATE TABLE answer');
        $this->addSql('TRUNCATE TABLE result');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
        $this->addSql('ALTER TABLE answer CHANGE answer answer VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE formula DROP formula_partial');
        $this->addSql('ALTER TABLE answer DROP FOREIGN KEY FK_DADD4A257A7B643');
        $this->addSql('ALTER TABLE answer ADD CONSTRAINT FK_DADD4A257A7B643 FOREIGN KEY (result_id) REFERENCES result (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('TRUNCATE TABLE answer');
        $this->addSql('TRUNCATE TABLE result');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
        $this->addSql('ALTER TABLE answer CHANGE answer answer SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE formula ADD formula_partial VARCHAR(250) NOT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE answer DROP FOREIGN KEY FK_DADD4A257A7B643');
        $this->addSql('ALTER TABLE answer ADD CONSTRAINT FK_DADD4A257A7B643 FOREIGN KEY (result_id) REFERENCES result (id)');
    }
}
