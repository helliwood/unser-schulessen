<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250623101153 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD school_authority_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6494ADE33E5 FOREIGN KEY (school_authority_id) REFERENCES school_authority (id)');
        $this->addSql('CREATE INDEX IDX_8D93D6494ADE33E5 ON user (school_authority_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6494ADE33E5');
        $this->addSql('DROP INDEX IDX_8D93D6494ADE33E5 ON user');
        $this->addSql('ALTER TABLE user DROP school_authority_id');
    }
}
