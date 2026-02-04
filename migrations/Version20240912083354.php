<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240912083354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C12DE62210');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C12DE62210 FOREIGN KEY (previous_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E2DE62210');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E2DE62210 FOREIGN KEY (previous_id) REFERENCES question (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAFD7850ECE');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFD7850ECE FOREIGN KEY (based_on_id) REFERENCES questionnaire (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C12DE62210');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C12DE62210 FOREIGN KEY (previous_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E2DE62210');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E2DE62210 FOREIGN KEY (previous_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAFD7850ECE');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFD7850ECE FOREIGN KEY (based_on_id) REFERENCES questionnaire (id)');
    }
}
