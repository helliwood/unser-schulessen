<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201002105608 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("UPDATE user_has_school SET role = 'ROLE_MENSA_AG' WHERE role='ROLE_READ';");
        $this->addSql("UPDATE user_has_school SET role = 'ROLE_MENSA_AG' WHERE role='ROLE_WRITE';");
        $this->addSql("UPDATE user_has_school SET role = 'ROLE_MENSA_AG' WHERE role='ROLE_ADMINISTRATIVE_MEMBER';");


    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
