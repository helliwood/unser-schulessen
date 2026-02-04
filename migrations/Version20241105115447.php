<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241105115447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_has_school DROP FOREIGN KEY FK_72DB0BCC638D302');

        if ($_ENV['APP_STATE_COUNTRY'] == 'sl') {
            $this->addSql('UPDATE person_type SET name="Schulleitung/Leitung FGTS" WHERE name="Schulleitung"');
            $this->addSql('UPDATE user_has_school SET person_type="Schulleitung/Leitung FGTS" WHERE person_type="Schulleitung"');
        }

        $this->addSql('ALTER TABLE user_has_school ADD CONSTRAINT FK_72DB0BCC638D302 FOREIGN KEY (person_type) REFERENCES person_type (name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_has_school DROP FOREIGN KEY FK_72DB0BCC638D302');

        if ($_ENV['APP_STATE_COUNTRY'] == 'sl') {
            $this->addSql('UPDATE person_type SET name="Schulleitung" WHERE name="Schulleitung/Leitung FGTS"');
            $this->addSql('UPDATE user_has_school SET person_type="Schulleitung" WHERE person_type="Schulleitung/Leitung FGTS"');
        }

        $this->addSql('ALTER TABLE user_has_school ADD CONSTRAINT FK_72DB0BCC638D302 FOREIGN KEY (person_type) REFERENCES person_type (name)');
    }
}
