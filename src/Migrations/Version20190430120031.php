<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190430120031 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_has_school (user_id INT UNSIGNED NOT NULL, school_id INT UNSIGNED NOT NULL, person_type VARCHAR(100) NOT NULL, role VARCHAR(190) NOT NULL, state SMALLINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, responded_at DATETIME DEFAULT NULL, INDEX IDX_72DB0BCCA76ED395 (user_id), INDEX IDX_72DB0BCCC32A47EE (school_id), INDEX IDX_72DB0BCC638D302 (person_type), PRIMARY KEY(user_id, school_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_has_school ADD CONSTRAINT FK_72DB0BCCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE user_has_school ADD CONSTRAINT FK_72DB0BCCC32A47EE FOREIGN KEY (school_id) REFERENCES school (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE user_has_school ADD CONSTRAINT FK_72DB0BCC638D302 FOREIGN KEY (person_type) REFERENCES person_type (name) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE user ADD current_school_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649180CF642 FOREIGN KEY (current_school_id) REFERENCES school (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8D93D649180CF642 ON user (current_school_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user_has_school');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649180CF642');
        $this->addSql('DROP INDEX IDX_8D93D649180CF642 ON user');
        $this->addSql('ALTER TABLE user DROP current_school_id');
    }
}
