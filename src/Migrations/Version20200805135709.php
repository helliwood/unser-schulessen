<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200805135709 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');

        $this->addSql("UPDATE user_has_school SET person_type = 'Mitglied der Mensa AG' WHERE person_type='Eltern' OR person_type='Lehrkraft' OR person_type='Schüler/in';");
        $this->addSql("UPDATE user_has_school SET person_type = 'Mitglied der Mensa AG' WHERE person_type='Mitglied Mensa AG' ;");
        $this->addSql("UPDATE user_has_school SET person_type = 'Schulleitung' WHERE person_type='Schulleiter';");
        $this->addSql("UPDATE user_has_school SET person_type = 'Gast' WHERE person_type='Sonstige';");
        $this->addSql("UPDATE user_has_school SET person_type = 'Küche/Speisenanbieter' WHERE person_type='Verpflegungsanbieter';");
        $this->addSql("UPDATE user_has_school SET person_type = 'Verpflegungsbeauftragte(r)' WHERE person_type='Verpflegungsbeauftragter';");



        $this->addSql("UPDATE person_type SET name = 'Verpflegungsbeauftragte(r)' WHERE name='Verpflegungsbeauftragter';");
        $this->addSql("UPDATE person_type SET name = 'Küche/Speisenanbieter' WHERE name='Verpflegungsanbieter';");
        $this->addSql("UPDATE person_type SET name = 'Schulleitung' WHERE name='Schulleiter';");
        $this->addSql("UPDATE person_type SET name = 'Gast' WHERE name='Sonstige';");
        $this->addSql("UPDATE person_type SET name = 'Mitglied der Mensa AG' WHERE name='Mitglied Mensa AG';");

        $this->addSql("DELETE FROM person_type WHERE name IN ('Eltern', 'Lehrkraft', 'Schüler');");
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');

    }

    public function down(Schema $schema) : void
    {


    }
}
