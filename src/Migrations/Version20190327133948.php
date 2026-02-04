<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190327133948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE school (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql("CREATE TABLE user (id INT UNSIGNED AUTO_INCREMENT NOT NULL, school_id INT UNSIGNED DEFAULT NULL, email VARCHAR(190) NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(50) NOT NULL, is_active TINYINT(1) NOT NULL, roles LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', current_login DATETIME DEFAULT NULL, last_login DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D649C32A47EE (school_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649C32A47EE FOREIGN KEY (school_id) REFERENCES school (id)');
        $this->addSql("INSERT INTO school (id, name, created_at) VALUES (1, 'Gymnasium Heilbronn', '2019-03-27 14:02:48');");
        $this->addSql("INSERT INTO user (id, school_id, email, password, first_name, last_name, is_active, roles, current_login, last_login) VALUES (1, 1, 'mk@k2dev.de', '$2y$13$2P0qdvoVTmbzJSDNybMayeehoANXNHec.LoMrcUmcpLyrELAGI0ke', 'Maurice', 'Karg', 1, '[]', null, null);");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649C32A47EE');
        $this->addSql('DROP TABLE school');
        $this->addSql('DROP TABLE user');
    }
}
