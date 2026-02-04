<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211006115134 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE master_data DROP FOREIGN KEY FK_8646DE0AC32A47EE');
        $this->addSql('ALTER TABLE master_data ADD CONSTRAINT FK_8646DE0AC32A47EE FOREIGN KEY (school_id) REFERENCES school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10CC32A47EE');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CC32A47EE FOREIGN KEY (school_id) REFERENCES school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE person DROP FOREIGN KEY FK_34DCD176C32A47EE');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT FK_34DCD176C32A47EE FOREIGN KEY (school_id) REFERENCES school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC113C32A47EE');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC113C32A47EE FOREIGN KEY (school_id) REFERENCES school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE survey_survey DROP FOREIGN KEY FK_85515390C32A47EE');
        $this->addSql('ALTER TABLE survey_survey ADD CONSTRAINT FK_85515390C32A47EE FOREIGN KEY (school_id) REFERENCES school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE to_do DROP FOREIGN KEY FK_1249EDA07A7B643');
        $this->addSql('ALTER TABLE to_do ADD CONSTRAINT FK_1249EDA07A7B643 FOREIGN KEY (result_id) REFERENCES result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE to_do_new DROP FOREIGN KEY FK_18DB824DC32A47EE');
        $this->addSql('ALTER TABLE to_do_new ADD CONSTRAINT FK_18DB824DC32A47EE FOREIGN KEY (school_id) REFERENCES school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_has_school DROP FOREIGN KEY FK_72DB0BCCA76ED395');
        $this->addSql('ALTER TABLE user_has_school DROP FOREIGN KEY FK_72DB0BCCC32A47EE');
        $this->addSql('ALTER TABLE user_has_school ADD CONSTRAINT FK_72DB0BCCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_has_school ADD CONSTRAINT FK_72DB0BCCC32A47EE FOREIGN KEY (school_id) REFERENCES school (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE master_data DROP FOREIGN KEY FK_8646DE0AC32A47EE');
        $this->addSql('ALTER TABLE master_data ADD CONSTRAINT FK_8646DE0AC32A47EE FOREIGN KEY (school_id) REFERENCES school (id)');
        $this->addSql('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10CC32A47EE');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CC32A47EE FOREIGN KEY (school_id) REFERENCES school (id)');
        $this->addSql('ALTER TABLE person DROP FOREIGN KEY FK_34DCD176C32A47EE');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT FK_34DCD176C32A47EE FOREIGN KEY (school_id) REFERENCES school (id)');
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC113C32A47EE');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC113C32A47EE FOREIGN KEY (school_id) REFERENCES school (id)');
        $this->addSql('ALTER TABLE survey_survey DROP FOREIGN KEY FK_85515390C32A47EE');
        $this->addSql('ALTER TABLE survey_survey ADD CONSTRAINT FK_85515390C32A47EE FOREIGN KEY (school_id) REFERENCES school (id)');
        $this->addSql('ALTER TABLE to_do DROP FOREIGN KEY FK_1249EDA07A7B643');
        $this->addSql('ALTER TABLE to_do ADD CONSTRAINT FK_1249EDA07A7B643 FOREIGN KEY (result_id) REFERENCES result (id)');
        $this->addSql('ALTER TABLE to_do_new DROP FOREIGN KEY FK_18DB824DC32A47EE');
        $this->addSql('ALTER TABLE to_do_new ADD CONSTRAINT FK_18DB824DC32A47EE FOREIGN KEY (school_id) REFERENCES school (id)');
        $this->addSql('ALTER TABLE user_has_school DROP FOREIGN KEY FK_72DB0BCCA76ED395');
        $this->addSql('ALTER TABLE user_has_school DROP FOREIGN KEY FK_72DB0BCCC32A47EE');
        $this->addSql('ALTER TABLE user_has_school ADD CONSTRAINT FK_72DB0BCCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_has_school ADD CONSTRAINT FK_72DB0BCCC32A47EE FOREIGN KEY (school_id) REFERENCES school (id)');
    }
}
