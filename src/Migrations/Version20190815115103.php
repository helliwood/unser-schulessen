<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190815115103 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ideabox ADD previous_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ideabox ADD CONSTRAINT FK_358762771E27F6BF FOREIGN KEY (question_id) REFERENCES question (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE ideabox ADD CONSTRAINT FK_358762772DE62210 FOREIGN KEY (previous_id) REFERENCES ideabox (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_358762772DE62210 ON ideabox (previous_id)');
        $this->addSql('ALTER TABLE question DROP INDEX UNIQ_B6F7494E2DE62210, ADD INDEX IDX_B6F7494E2DE62210 (previous_id)');
        $this->addSql('ALTER TABLE category DROP INDEX UNIQ_64C19C12DE62210, ADD INDEX IDX_64C19C12DE62210 (previous_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE category DROP INDEX IDX_64C19C12DE62210, ADD UNIQUE INDEX UNIQ_64C19C12DE62210 (previous_id)');
        $this->addSql('ALTER TABLE ideabox DROP FOREIGN KEY FK_358762771E27F6BF');
        $this->addSql('ALTER TABLE ideabox DROP FOREIGN KEY FK_358762772DE62210');
        $this->addSql('DROP INDEX IDX_358762772DE62210 ON ideabox');
        $this->addSql('ALTER TABLE ideabox DROP previous_id');
        $this->addSql('ALTER TABLE question DROP INDEX IDX_B6F7494E2DE62210, ADD UNIQUE INDEX UNIQ_B6F7494E2DE62210 (previous_id)');
    }
}
