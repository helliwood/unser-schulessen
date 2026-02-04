<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211006112512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE survey_survey_voucher DROP FOREIGN KEY FK_E4B3E7CEB3FE509D');
        $this->addSql('ALTER TABLE survey_survey_voucher ADD CONSTRAINT FK_E4B3E7CEB3FE509D FOREIGN KEY (survey_id) REFERENCES survey_survey (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE survey_survey_voucher DROP FOREIGN KEY FK_E4B3E7CEB3FE509D');
        $this->addSql('ALTER TABLE survey_survey_voucher ADD CONSTRAINT FK_E4B3E7CEB3FE509D FOREIGN KEY (survey_id) REFERENCES survey_survey (id)');
    }
}
