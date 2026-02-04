<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190725102949 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE formula (question_id INT UNSIGNED NOT NULL, formula_true VARCHAR(250) NOT NULL, formula_false VARCHAR(250) NOT NULL, formula_partial VARCHAR(250) NOT NULL, PRIMARY KEY(question_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE formula ADD CONSTRAINT FK_673158811E27F6BF FOREIGN KEY (question_id) REFERENCES question (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX UNIQ_B6F7494E12469DE2F5299398 ON question');
        $this->addSql('ALTER TABLE question ADD `type` VARCHAR(50) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B6F7494E12469DE2B6F7494E ON question (category_id, question)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE formula');
        $this->addSql('DROP INDEX UNIQ_B6F7494E12469DE2B6F7494E ON question');
        $this->addSql('ALTER TABLE question DROP `type`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B6F7494E12469DE2F5299398 ON question (category_id, `order`)');
    }
}
