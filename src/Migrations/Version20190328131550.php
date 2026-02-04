<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190328131550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE address (id INT UNSIGNED AUTO_INCREMENT NOT NULL, street VARCHAR(50) DEFAULT NULL, postalcode VARCHAR(5) DEFAULT NULL, city VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE person (id INT UNSIGNED AUTO_INCREMENT NOT NULL, salutation VARCHAR(50) DEFAULT NULL, academic_title VARCHAR(25) DEFAULT NULL, first_name VARCHAR(50) DEFAULT NULL, last_name VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE school ADD address_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE school ADD CONSTRAINT FK_F99EDABBF5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id) ON DELETE RESTRICT');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F99EDABBF5B7AF75 ON school (address_id)');
        $this->addSql('ALTER TABLE user ADD person_id INT UNSIGNED NOT NULL, DROP first_name, DROP last_name');

        $this->addSql("INSERT INTO person (id, salutation, academic_title, first_name, last_name) VALUES (1, 'Herr', NULL, 'Maurice', 'Karg');");
        $this->addSql("UPDATE user SET person_id = 1 WHERE id = 1");

        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE RESTRICT');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649217BBB47 ON user (person_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE school DROP FOREIGN KEY FK_F99EDABBF5B7AF75');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649217BBB47');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP TABLE person');
        $this->addSql('DROP INDEX UNIQ_F99EDABBF5B7AF75 ON school');
        $this->addSql('ALTER TABLE school DROP address_id');
        $this->addSql('DROP INDEX UNIQ_8D93D649217BBB47 ON user');
        $this->addSql('ALTER TABLE user ADD first_name VARCHAR(50) NOT NULL COLLATE utf8mb4_unicode_ci, ADD last_name VARCHAR(50) NOT NULL COLLATE utf8mb4_unicode_ci, DROP person_id');
    }
}
