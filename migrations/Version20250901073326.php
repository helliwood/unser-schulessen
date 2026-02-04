<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250901073326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $startYear = 2025;
        $count = 25;

        for ($i = 0; $i < $count; $i++) {
            $year = $startYear + $i;
            $nextYear = $year + 1;

            $yearStr = (string)$year;
            $label = sprintf('%d/%d', $year, $nextYear);
            $periodBegin = sprintf('%d-09-01', $year);
            $periodEnd = sprintf('%d-08-31', $nextYear);

            // Falls Primärschlüssel schon existiert: ignoriere Eintrag
//            $this->addSql(
//                'INSERT IGNORE INTO `mein-schulessen`.school_year (year, label, period_begin, period_end)
//             VALUES (:year, :label, :begin, :end)',
//                [
//                    'year' => $yearStr,
//                    'label' => $label,
//                    'begin' => $periodBegin,
//                    'end'   => $periodEnd,
//                ]
//            );
        }
    }


    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
