<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190813123138 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE to_do_item (id INT UNSIGNED AUTO_INCREMENT NOT NULL, todo_id INT UNSIGNED NOT NULL, answer_id INT UNSIGNED NOT NULL, INDEX IDX_11B395EAEA1EBC33 (todo_id), INDEX IDX_11B395EAAA334807 (answer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE action_plan (id INT UNSIGNED AUTO_INCREMENT NOT NULL, todo_item_id INT UNSIGNED NOT NULL, created_by_id INT UNSIGNED DEFAULT NULL, what VARCHAR(1024) NOT NULL, who VARCHAR(1024) NOT NULL, `when` DATE NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_ABBBE073C766982F (todo_item_id), INDEX IDX_ABBBE073B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE to_do (id INT UNSIGNED AUTO_INCREMENT NOT NULL, created_by_id INT UNSIGNED DEFAULT NULL, result_id INT UNSIGNED NOT NULL, name VARCHAR(150) NOT NULL, created_at DATETIME NOT NULL, archived TINYINT(1) NOT NULL, INDEX IDX_1249EDA0B03A8386 (created_by_id), INDEX IDX_1249EDA07A7B643 (result_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE to_do_item ADD CONSTRAINT FK_11B395EAEA1EBC33 FOREIGN KEY (todo_id) REFERENCES to_do (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE to_do_item ADD CONSTRAINT FK_11B395EAAA334807 FOREIGN KEY (answer_id) REFERENCES answer (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE action_plan ADD CONSTRAINT FK_ABBBE073C766982F FOREIGN KEY (todo_item_id) REFERENCES to_do_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE action_plan ADD CONSTRAINT FK_ABBBE073B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE to_do ADD CONSTRAINT FK_1249EDA0B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE to_do ADD CONSTRAINT FK_1249EDA07A7B643 FOREIGN KEY (result_id) REFERENCES result (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE survey_question DROP FOREIGN KEY FK_EA000F6912469DE2');
        $this->addSql('ALTER TABLE survey_question ADD CONSTRAINT FK_EA000F6912469DE2 FOREIGN KEY (category_id) REFERENCES survey_category (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE action_plan DROP FOREIGN KEY FK_ABBBE073C766982F');
        $this->addSql('ALTER TABLE to_do_item DROP FOREIGN KEY FK_11B395EAEA1EBC33');
        $this->addSql('DROP TABLE to_do_item');
        $this->addSql('DROP TABLE action_plan');
        $this->addSql('DROP TABLE to_do');
        $this->addSql('ALTER TABLE survey_question DROP FOREIGN KEY FK_EA000F6912469DE2');
        $this->addSql('ALTER TABLE survey_question ADD CONSTRAINT FK_EA000F6912469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
    }
}
