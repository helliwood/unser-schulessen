<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201027120709 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE user_has_school SET person_type="Schulleitung" WHERE person_type="Administrator"');
        $this->addSql('UPDATE user_has_school SET role="ROLE_HEADMASTER" WHERE person_type="ROLE_ADMIN"');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('DELETE FROM person_type WHERE name="Administrator"');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
