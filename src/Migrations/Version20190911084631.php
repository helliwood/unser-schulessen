<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190911084631 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE to_do_item DROP FOREIGN KEY FK_11B395EAEA1EBC33');
        $this->addSql('ALTER TABLE to_do_item ADD CONSTRAINT FK_11B395EAEA1EBC33 FOREIGN KEY (todo_id) REFERENCES to_do (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE to_do_item DROP FOREIGN KEY FK_11B395EAEA1EBC33');
        $this->addSql('ALTER TABLE to_do_item ADD CONSTRAINT FK_11B395EAEA1EBC33 FOREIGN KEY (todo_id) REFERENCES to_do (id)');
    }
}
