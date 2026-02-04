<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240912092232 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ideaboxes_ideabox_icons DROP FOREIGN KEY FK_D50F3AB713E8A476');
        $this->addSql('ALTER TABLE ideaboxes_ideabox_icons DROP FOREIGN KEY FK_D50F3AB7F039E09C');
        $this->addSql('ALTER TABLE ideaboxes_ideabox_icons ADD CONSTRAINT FK_D50F3AB713E8A476 FOREIGN KEY (ideabox_id) REFERENCES ideabox_icon (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ideaboxes_ideabox_icons ADD CONSTRAINT FK_D50F3AB7F039E09C FOREIGN KEY (ideabox_icon_id) REFERENCES ideabox (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ideaboxes_ideabox_icons DROP FOREIGN KEY FK_D50F3AB7F039E09C');
        $this->addSql('ALTER TABLE ideaboxes_ideabox_icons DROP FOREIGN KEY FK_D50F3AB713E8A476');
        $this->addSql('ALTER TABLE ideaboxes_ideabox_icons ADD CONSTRAINT FK_D50F3AB7F039E09C FOREIGN KEY (ideabox_icon_id) REFERENCES ideabox (id)');
        $this->addSql('ALTER TABLE ideaboxes_ideabox_icons ADD CONSTRAINT FK_D50F3AB713E8A476 FOREIGN KEY (ideabox_id) REFERENCES ideabox_icon (id)');
    }
}
