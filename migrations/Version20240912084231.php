<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240912084231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ideabox DROP FOREIGN KEY FK_358762772DE62210');
        $this->addSql('ALTER TABLE ideabox ADD CONSTRAINT FK_358762772DE62210 FOREIGN KEY (previous_id) REFERENCES ideabox (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ideabox DROP FOREIGN KEY FK_358762772DE62210');
        $this->addSql('ALTER TABLE ideabox ADD CONSTRAINT FK_358762772DE62210 FOREIGN KEY (previous_id) REFERENCES ideabox (id)');
    }
}
