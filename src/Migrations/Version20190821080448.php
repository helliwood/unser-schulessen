<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190821080448 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE questionnaire ADD based_on_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFD7850ECE FOREIGN KEY (based_on_id) REFERENCES questionnaire (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_7A64DAFD7850ECE ON questionnaire (based_on_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAFD7850ECE');
        $this->addSql('DROP INDEX IDX_7A64DAFD7850ECE ON questionnaire');
        $this->addSql('ALTER TABLE questionnaire DROP based_on_id');
    }
}
