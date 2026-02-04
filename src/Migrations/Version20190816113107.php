<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190816113107 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE to_do ADD archived_by_id INT UNSIGNED DEFAULT NULL, ADD archived_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE to_do ADD CONSTRAINT FK_1249EDA077BE2925 FOREIGN KEY (archived_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_1249EDA077BE2925 ON to_do (archived_by_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE to_do DROP FOREIGN KEY FK_1249EDA077BE2925');
        $this->addSql('DROP INDEX IDX_1249EDA077BE2925 ON to_do');
        $this->addSql('ALTER TABLE to_do DROP archived_by_id, DROP archived_at');
    }
}
