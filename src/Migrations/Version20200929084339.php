<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200929084339 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("UPDATE user_has_school SET role = 'ROLE_MENSA_AG' WHERE person_type='Eltern';");
        $this->addSql("UPDATE user_has_school SET role = 'ROLE_MENSA_AG' WHERE person_type='Lehrkraft';");
        $this->addSql("UPDATE user_has_school SET role = 'ROLE_MENSA_AG' WHERE person_type='Mitglied Mensa AG';");
        $this->addSql("UPDATE user_has_school SET role = 'ROLE_MENSA_AG' WHERE person_type='Schüler/in';");
        $this->addSql("UPDATE user_has_school SET role = 'ROLE_HEADMASTER' WHERE person_type='Schulleiter/in';");
        $this->addSql("UPDATE user_has_school SET role = 'ROLE_SCHOOL_AUTHORITIES' WHERE person_type='Schulträger';");
        $this->addSql("UPDATE user_has_school SET role = 'ROLE_GUEST' WHERE person_type='Sonstige';");
        $this->addSql("UPDATE user_has_school SET role = 'ROLE_KITCHEN' WHERE person_type='Verpflegungsanbieter';");
        $this->addSql("UPDATE user_has_school SET role = 'ROLE_FOOD_COMMISSIONER' WHERE person_type='Verpflegungsbeauftragter';");

        $this->addSql("UPDATE user_has_school SET role = 'ROLE_GUEST' WHERE person_type='Gast';");
        $this->addSql("UPDATE user_has_school SET role = 'ROLE_KITCHEN' WHERE person_type='Küche/Speisenanbieter';");
        $this->addSql("UPDATE user_has_school SET role = 'ROLE_MENSA_AG' WHERE person_type='Mitglied der Mensa AG';");
        $this->addSql("UPDATE user_has_school SET role = 'ROLE_HEADMASTER' WHERE person_type='Schulleitung';");
        $this->addSql("UPDATE user_has_school SET role = 'ROLE_FOOD_COMMISSIONER' WHERE person_type='Verpflegungsbeauftragte(r)';");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
