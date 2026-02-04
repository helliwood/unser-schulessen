<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190329093841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE category (id INT UNSIGNED AUTO_INCREMENT NOT NULL, previous_id INT UNSIGNED DEFAULT NULL, questionnaire_id INT UNSIGNED NOT NULL, name VARCHAR(150) NOT NULL, `order` SMALLINT NOT NULL, UNIQUE INDEX UNIQ_64C19C12DE62210 (previous_id), INDEX IDX_64C19C1CE07E8FF (questionnaire_id), UNIQUE INDEX UNIQ_64C19C1CE07E8FF5E237E06 (questionnaire_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE questionnaire (id INT UNSIGNED AUTO_INCREMENT NOT NULL, created_by_id INT UNSIGNED NOT NULL, name VARCHAR(190) NOT NULL, date DATETIME NOT NULL, UNIQUE INDEX UNIQ_7A64DAF5E237E06 (name), INDEX IDX_7A64DAFB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE question (id INT UNSIGNED AUTO_INCREMENT NOT NULL, category_id INT UNSIGNED NOT NULL, previous_id INT UNSIGNED DEFAULT NULL, question VARCHAR(190) NOT NULL, `order` SMALLINT NOT NULL, UNIQUE INDEX UNIQ_B6F7494EB6F7494E (question), INDEX IDX_B6F7494E12469DE2 (category_id), UNIQUE INDEX UNIQ_B6F7494E2DE62210 (previous_id), UNIQUE INDEX UNIQ_B6F7494E12469DE2F5299398 (category_id, `order`), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE result (id INT UNSIGNED AUTO_INCREMENT NOT NULL, school_id INT UNSIGNED NOT NULL, created_by_id INT UNSIGNED NOT NULL, questionnaire_id INT UNSIGNED NOT NULL, date DATETIME NOT NULL, INDEX IDX_136AC113C32A47EE (school_id), INDEX IDX_136AC113B03A8386 (created_by_id), INDEX IDX_136AC113CE07E8FF (questionnaire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE answer (id INT UNSIGNED AUTO_INCREMENT NOT NULL, question_id INT UNSIGNED NOT NULL, result_id INT UNSIGNED NOT NULL, answer SMALLINT NOT NULL, INDEX IDX_DADD4A251E27F6BF (question_id), INDEX IDX_DADD4A257A7B643 (result_id), UNIQUE INDEX UNIQ_DADD4A251E27F6BF7A7B643 (question_id, result_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C12DE62210 FOREIGN KEY (previous_id) REFERENCES category (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1CE07E8FF FOREIGN KEY (questionnaire_id) REFERENCES questionnaire (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E2DE62210 FOREIGN KEY (previous_id) REFERENCES question (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC113C32A47EE FOREIGN KEY (school_id) REFERENCES school (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC113B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC113CE07E8FF FOREIGN KEY (questionnaire_id) REFERENCES questionnaire (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE answer ADD CONSTRAINT FK_DADD4A251E27F6BF FOREIGN KEY (question_id) REFERENCES question (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE answer ADD CONSTRAINT FK_DADD4A257A7B643 FOREIGN KEY (result_id) REFERENCES result (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE user ADD created_at DATETIME NOT NULL');


        /*
        $this->addSql("INSERT INTO category (id, `name`, `order`) VALUES (1, 'Geschmack und Vielfalt', 1);");
        $this->addSql("INSERT INTO category (id, `name`, `order`) VALUES (2, 'Ausgewogenheit', 2);");
        $this->addSql("INSERT INTO category (id, `name`, `order`) VALUES (3, 'Mensagestaltung und Zeiten', 3);");
        $this->addSql("INSERT INTO category (id, `name`, `order`) VALUES (4, 'Organisatorisches', 4);");
        $this->addSql("INSERT INTO category (id, `name`, `order`) VALUES (5, 'Sauberkeit', 5);");
        $this->addSql("INSERT INTO category (id, `name`, `order`) VALUES (6, 'Beschwerden', 6);");
        $this->addSql("INSERT INTO category (id, `name`, `order`) VALUES (7, 'Vertragliche Regelungen', 7);");
        $this->addSql("INSERT INTO category (id, `name`, `order`) VALUES (8, 'Ernährungsbildung', 8);");
        */
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C12DE62210');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E12469DE2');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1CE07E8FF');
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC113CE07E8FF');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E2DE62210');
        $this->addSql('ALTER TABLE answer DROP FOREIGN KEY FK_DADD4A251E27F6BF');
        $this->addSql('ALTER TABLE answer DROP FOREIGN KEY FK_DADD4A257A7B643');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE questionnaire');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE result');
        $this->addSql('DROP TABLE answer');
        $this->addSql('ALTER TABLE user DROP created_at');
    }
}
