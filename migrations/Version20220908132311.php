<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220908132311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO person_type SET name="Verpflegungsausschuss"');
        $this->addSql('UPDATE user_has_school SET person_type="Verpflegungsausschuss" WHERE person_type="Mitglied der Mensa AG"');
        $this->addSql('UPDATE person SET person_type="Verpflegungsausschuss" WHERE person_type="Mitglied der Mensa AG"');
        $this->addSql('DELETE FROM person_type WHERE name="Mitglied der Mensa AG"');
        $this->addSql('INSERT INTO ideabox_icon ( category, icon) VALUES ( \'Nachhaltigkeit\', \'fas fa-leaf\')');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('INSERT INTO person_type SET name="Mitglied der Mensa AG"');
        $this->addSql('UPDATE user_has_school SET person_type="Mitglied der Mensa AG" WHERE person_type="Verpflegungsausschuss"');
        $this->addSql('UPDATE person SET person_type="Mitglied der Mensa AG" WHERE person_type="Verpflegungsausschuss"');
        $this->addSql('DELETE FROM person_type WHERE name="Verpflegungsausschuss"');
        $this->addSql('DELETE FROM ideabox_icon WHERE category="Nachhaltigkeit" AND icon="fas fa-leaf"');

    }
}
