<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190813080910 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ideabox (id INT AUTO_INCREMENT NOT NULL, question_id INT UNSIGNED NOT NULL, idea VARCHAR(190) NOT NULL, `order` SMALLINT NOT NULL, INDEX IDX_358762771E27F6BF (question_id), UNIQUE INDEX UNIQ_358762771E27F6BFA8BCA45 (question_id, idea), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ideaboxes_ideabox_icons (ideabox_icon_id INT NOT NULL, ideabox_id INT NOT NULL, INDEX IDX_D50F3AB7F039E09C (ideabox_icon_id), INDEX IDX_D50F3AB713E8A476 (ideabox_id), PRIMARY KEY(ideabox_icon_id, ideabox_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ideabox_icon (id INT AUTO_INCREMENT NOT NULL, category VARCHAR(255) NOT NULL, icon VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ideaboxes_ideabox_icons ADD CONSTRAINT FK_D50F3AB7F039E09C FOREIGN KEY (ideabox_icon_id) REFERENCES ideabox (id)');
        $this->addSql('ALTER TABLE ideaboxes_ideabox_icons ADD CONSTRAINT FK_D50F3AB713E8A476 FOREIGN KEY (ideabox_id) REFERENCES ideabox_icon (id)');
        $this->addSql('INSERT INTO ideabox_icon (id, category, icon) VALUES (1, \'Aktion/Projekt Schulteam\', \'far fa-star\')');
        $this->addSql('INSERT INTO ideabox_icon (id, category, icon) VALUES (2, \'Kommunikation\', \'far fa-comments\')');
        $this->addSql('INSERT INTO ideabox_icon (id, category, icon) VALUES (3, \'Prüfen\', \'far fa-check-square"\')');
        $this->addSql('INSERT INTO ideabox_icon (id, category, icon) VALUES (4, \'Chefsache\', \'fas fa-user-tie\')');
        $this->addSql('INSERT INTO ideabox_icon (id, category, icon) VALUES (5, \'Ernährungsbildung\', \'fas fa-apple-alt\')');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ideaboxes_ideabox_icons DROP FOREIGN KEY FK_D50F3AB713E8A476');
        $this->addSql('DROP TABLE ideaboxes_ideabox_icons');
        $this->addSql('DROP TABLE ideabox_icon');
    }
}
