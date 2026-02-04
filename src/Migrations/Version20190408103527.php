<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190408103527 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE person_type (name VARCHAR(100) NOT NULL, PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649C32A47EE');
        $this->addSql('DROP INDEX IDX_8D93D649C32A47EE ON user');
        $this->addSql('ALTER TABLE user DROP school_id');
        $this->addSql('ALTER TABLE person ADD person_type VARCHAR(100) DEFAULT NULL, ADD school_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT FK_34DCD176638D302 FOREIGN KEY (person_type) REFERENCES person_type (name) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT FK_34DCD176C32A47EE FOREIGN KEY (school_id) REFERENCES school (id)');
        $this->addSql('CREATE INDEX IDX_34DCD176638D302 ON person (person_type)');
        $this->addSql('CREATE INDEX IDX_34DCD176C32A47EE ON person (school_id)');

        $this->addSql("INSERT INTO person_type (name) VALUES ('Schulleiter'), ('Mitglied Mensa AG'), ('Verpflegungsbeauftragter');");

        $this->addSql('ALTER TABLE user ADD state SMALLINT DEFAULT 0 NOT NULL, DROP is_active');
        $this->addSql('UPDATE user SET `state` = 1 WHERE `id` = 1');

        $this->addSql('ALTER TABLE user CHANGE password password VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE person DROP FOREIGN KEY FK_34DCD176638D302');
        $this->addSql('DROP TABLE person_type');
        $this->addSql('ALTER TABLE person DROP FOREIGN KEY FK_34DCD176C32A47EE');
        $this->addSql('DROP INDEX IDX_34DCD176638D302 ON person');
        $this->addSql('DROP INDEX IDX_34DCD176C32A47EE ON person');
        $this->addSql('ALTER TABLE person DROP person_type, DROP school_id');
        $this->addSql('ALTER TABLE user ADD school_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649C32A47EE FOREIGN KEY (school_id) REFERENCES school (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649C32A47EE ON user (school_id)');

        $this->addSql('ALTER TABLE user ADD is_active TINYINT(1) NOT NULL, DROP state');

        $this->addSql('ALTER TABLE user CHANGE password password VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci');
    }
}
