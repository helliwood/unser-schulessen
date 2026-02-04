<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200422141910 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql("UPDATE person_type SET name = 'Schulleiter' WHERE name='Schulleiter/in';");
        $this->addSql("UPDATE person_type SET name = 'Schüler' WHERE name='Schüler/in';");
        $this->addSql("UPDATE user_has_school SET person_type = 'Schulleiter' WHERE person_type='Schulleiter/in';");
        $this->addSql("UPDATE user_has_school SET person_type = 'Schüler' WHERE person_type='Schüler/in';");
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql("UPDATE person_type SET name = 'Schüler/in' WHERE name='Schüler';");
        $this->addSql("UPDATE person_type SET name = 'Schulleiter/in' WHERE name='Schulleiter';");
        $this->addSql("UPDATE user_has_school SET person_type = 'Schüler/in' WHERE person_type='Schüler';");
        $this->addSql("UPDATE user_has_school SET person_type = 'Schulleiter/in' WHERE person_type='Schulleiter';");
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }
}
