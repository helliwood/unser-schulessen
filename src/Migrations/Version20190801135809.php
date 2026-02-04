<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190801135809 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE result ADD last_edited_by_id INT UNSIGNED DEFAULT NULL, ADD finalised_by_id INT UNSIGNED DEFAULT NULL, ADD last_edited_at DATETIME DEFAULT NULL, ADD finalised TINYINT(1) DEFAULT \'0\' NOT NULL, ADD finalised_at DATETIME DEFAULT NULL, CHANGE date created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC113D48D54E8 FOREIGN KEY (last_edited_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC1131A836B51 FOREIGN KEY (finalised_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_136AC113D48D54E8 ON result (last_edited_by_id)');
        $this->addSql('CREATE INDEX IDX_136AC1131A836B51 ON result (finalised_by_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC113D48D54E8');
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC1131A836B51');
        $this->addSql('DROP INDEX IDX_136AC113D48D54E8 ON result');
        $this->addSql('DROP INDEX IDX_136AC1131A836B51 ON result');
        $this->addSql('ALTER TABLE result DROP last_edited_by_id, DROP finalised_by_id, DROP last_edited_at, DROP finalised, DROP finalised_at, CHANGE created_at date DATETIME NOT NULL');
    }
}
