<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241104094042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_has_school DROP FOREIGN KEY FK_72DB0BCC638D302');
        $this->addSql('UPDATE person_type SET name="Küche/Verpflegungsanbieter" WHERE name="Küche/Speisenanbieter"');
        $this->addSql('UPDATE user_has_school SET person_type="Küche/Verpflegungsanbieter" WHERE person_type="Küche/Speisenanbieter"');

        if ($_ENV['APP_STATE_COUNTRY'] == 'sl') {
            $this->addSql('UPDATE person_type SET name="Schulträger/Träger FGTS" WHERE name="Schulträger"');
            $this->addSql('UPDATE person_type SET name="Schulträger/Träger FGTS aktiv" WHERE name="Schulträger aktiv"');
            $this->addSql('INSERT IGNORE INTO person_type (name) VALUES ("Schulträger/Träger FGTS aktiv");');

            $this->addSql('UPDATE user_has_school SET person_type="Schulträger/Träger FGTS" WHERE person_type="Schulträger"');
            $this->addSql('UPDATE user_has_school SET person_type="Schulträger/Träger FGTS aktiv" WHERE person_type="Schulträger aktiv"');
        }

        $this->addSql('ALTER TABLE user_has_school ADD CONSTRAINT FK_72DB0BCC638D302 FOREIGN KEY (person_type) REFERENCES person_type (name)');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_has_school DROP FOREIGN KEY FK_72DB0BCC638D302');
        $this->addSql('UPDATE person_type SET name="Küche/Speisenanbieter" WHERE name="Küche/Verpflegungsanbieter"');
        $this->addSql('UPDATE user_has_school SET person_type="Küche/Speisenanbieter" WHERE person_type="Küche/Verpflegungsanbieter"');

        if ($_ENV['APP_STATE_COUNTRY'] == 'sl') {
            $this->addSql('UPDATE person_type SET name="Schulträger" WHERE name="Schulträger/Träger FGTS"');
            $this->addSql('UPDATE person_type SET name="Schulträger aktiv" WHERE name="Schulträger/Träger FGTS aktiv"');
            $this->addSql('DELETE FROM person_type WHERE name="Schulträger/Träger FGTS aktiv"');

            $this->addSql('UPDATE user_has_school SET person_type="Schulträger" WHERE person_type="Schulträger/Träger FGTS"');
            $this->addSql('UPDATE user_has_school SET person_type="Schulträger aktiv" WHERE person_type="Schulträger/Träger FGTS aktiv"');
        }

        $this->addSql('ALTER TABLE user_has_school ADD CONSTRAINT FK_72DB0BCC638D302 FOREIGN KEY (person_type) REFERENCES person_type (name)');

    }
}
