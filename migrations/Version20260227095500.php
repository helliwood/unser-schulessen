<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260227095500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add schoolAuthorityAccessAllowed flag to school';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE school ADD school_authority_access_allowed TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE school DROP school_authority_access_allowed');
    }
}
