<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190726135344 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_B6F7494EB6F7494E ON question');
        $this->addSql('ALTER TABLE category DROP INDEX UNIQ_64C19C12DE62210, ADD INDEX IDX_64C19C12DE62210 (previous_id)');
        $this->addSql('ALTER TABLE category DROP INDEX IDX_64C19C12DE62210, ADD UNIQUE INDEX UNIQ_64C19C12DE62210 (previous_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_B6F7494EB6F7494E ON question (question)');
        $this->addSql('ALTER TABLE category DROP INDEX IDX_64C19C12DE62210, ADD UNIQUE INDEX UNIQ_64C19C12DE62210 (previous_id)');
        $this->addSql('ALTER TABLE question DROP INDEX IDX_B6F7494E2DE62210, ADD UNIQUE INDEX UNIQ_B6F7494E2DE62210 (previous_id)');
    }
}
