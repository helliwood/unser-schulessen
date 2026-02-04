<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210603130548 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

    }

    public function postUp(Schema $schema): void
    {
        $toDoItems = $this->connection->fetchAllAssociative("SELECT * FROM to_do_item");
        if (count($toDoItems) > 1) {
            foreach ($toDoItems as $i => $toDoItem) {
                $toDo = $this->connection->fetchAssociative("SELECT * FROM to_do WHERE id=" . $toDoItem['todo_id']);
                $result = $this->connection->fetchAssociative("SELECT * FROM result WHERE id=" . $toDo['result_id']);
                $actionPlan = $this->connection->fetchAssociative("SELECT * FROM action_plan WHERE todo_item_id=" . $toDoItem['id']);
                //$this->connection->executeStatement("UPDATE Institute SET bankDetailsId = " . $in['fBankDetailsId'] . " WHERE id = " . $in['id']);
                $toDoNew = [
                    "school_id" => $result["school_id"],
                    "answer_id" => $toDoItem["answer_id"],
                    "created_by_id" => $toDo["created_by_id"],
                    "name" => $toDo["name"],
                    "completed" => $toDoItem["completed"],
                    "note" => $toDoItem["note"],
                    "created_at" => $toDo["created_at"],
                    "closed" => $toDo["archived"],
                    "closed_at" => $toDo["archived_at"],
                    "closed_by_id" => $toDo["archived_by_id"],
                ];
                $this->connection->insert("to_do_new", $toDoNew);
                $lastToDoId = $this->connection->lastInsertId();
                if ($actionPlan) {
                    $actionPlanNew = [
                        "to_do_id" => (int)$lastToDoId,
                        "created_by_id" => (int)$actionPlan["created_by_id"],
                        "what" => "Aktionsplan vom " . (new \DateTime($actionPlan["created_at"]))->format("d.m.Y"),
                        "how" => $actionPlan["what"],
                        "who" => $actionPlan["who"],
                        "`when`" => $actionPlan["when"],
                        "completed" => $toDoItem["completed"],
                        "closed" => $toDo["archived"],
                        "note" => $toDoItem["note"],
                        "created_at" => $actionPlan["created_at"],
                    ];
                    $this->connection->insert("action_plan_new", $actionPlanNew);
                }
            }

        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
