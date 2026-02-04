<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190606125222 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE master_data_entry (step VARCHAR(190) NOT NULL, `key` VARCHAR(190) NOT NULL, master_data_id INT UNSIGNED NOT NULL, value VARCHAR(2048) NOT NULL, INDEX IDX_6D80F361EBD24C54 (master_data_id), PRIMARY KEY(master_data_id, step, `key`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE master_data (id INT UNSIGNED AUTO_INCREMENT NOT NULL, school_id INT UNSIGNED NOT NULL, school_year VARCHAR(4) NOT NULL, finalised_by_id INT UNSIGNED DEFAULT NULL, finalised TINYINT(1) DEFAULT \'0\' NOT NULL, created_at DATETIME NOT NULL, finalised_at DATETIME DEFAULT NULL, INDEX IDX_8646DE0AC32A47EE (school_id), INDEX IDX_8646DE0AFAAAACDA (school_year), INDEX IDX_8646DE0A1A836B51 (finalised_by_id), UNIQUE INDEX MD_School_Version_unique (school_id, school_year), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE school_year (year VARCHAR(4) NOT NULL, label VARCHAR(255) NOT NULL, period_begin DATE NOT NULL, period_end DATE NOT NULL, PRIMARY KEY(year)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE master_data_entry ADD CONSTRAINT FK_6D80F361EBD24C54 FOREIGN KEY (master_data_id) REFERENCES master_data (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE master_data ADD CONSTRAINT FK_8646DE0AC32A47EE FOREIGN KEY (school_id) REFERENCES school (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE master_data ADD CONSTRAINT FK_8646DE0AFAAAACDA FOREIGN KEY (school_year) REFERENCES school_year (year) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE master_data ADD CONSTRAINT FK_8646DE0A1A836B51 FOREIGN KEY (finalised_by_id) REFERENCES user (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE master_data_entry DROP FOREIGN KEY FK_6D80F361EBD24C54');
        $this->addSql('ALTER TABLE master_data DROP FOREIGN KEY FK_8646DE0AFAAAACDA');
        $this->addSql('DROP TABLE master_data_entry');
        $this->addSql('DROP TABLE master_data');
        $this->addSql('DROP TABLE school_year');
    }
}
