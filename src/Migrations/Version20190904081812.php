<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190904081812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE school ADD headmaster VARCHAR(255) DEFAULT NULL, ADD phone_number VARCHAR(255) DEFAULT NULL, ADD fax_number VARCHAR(255) DEFAULT NULL, ADD email_address VARCHAR(255) DEFAULT NULL, ADD webpage VARCHAR(255) DEFAULT NULL, ADD education_authority VARCHAR(255) DEFAULT NULL, ADD school_type VARCHAR(255) DEFAULT NULL, ADD school_operator VARCHAR(255) DEFAULT NULL, ADD particularity VARCHAR(1024) DEFAULT NULL');
        $this->addSql("INSERT INTO person_type (name) VALUES ('Lehrkraft'), ('Schüler/in'), ('Eltern'), ('Schulträger'), ('Verpflegungsanbieter'), ('Sonstige');");

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql("UPDATE person_type SET name = 'Schulleiter/in' WHERE name='Schulleiter';");
        $this->addSql("UPDATE user_has_school SET person_type = 'Schulleiter/in' WHERE person_type='Schulleiter';");
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE school DROP headmaster, DROP phone_number, DROP fax_number, DROP email_address, DROP webpage, DROP education_authority, DROP school_type, DROP school_operator, DROP particularity');
        $this->addSql("DELETE FROM person_type WHERE name IN ('Lehrkraft', 'Schüler/in', 'Eltern', 'Schulträger', 'Verpflegungsanbieter', 'Sonstige');");
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql("UPDATE person_type SET name = 'Schulleiter' WHERE name='Schulleiter/in';");
        $this->addSql("UPDATE person_type SET name = 'Schulleiter' WHERE name='Schulleiter/in';");
        $this->addSql("UPDATE user_has_school SET person_type = 'Schulleiter' WHERE person_type='Schulleiter/in';");
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }
}
