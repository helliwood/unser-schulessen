<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 09.08.19
 * Time: 13:37
 */

namespace App\Entity\QualityCircle;

use App\Entity\QualityCheck\Answer;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ToDoItem-Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Entity(repositoryClass="App\Repository\QualityCircle\ToDoItemRepository")
 */
class ToDoItem implements \JsonSerializable
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    private $id;

    /**
     * @var ToDo
     * @ORM\ManyToOne(targetEntity="App\Entity\QualityCircle\ToDo", inversedBy="items")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $todo;

    /**
     * @var Answer
     * @ORM\ManyToOne(targetEntity="App\Entity\QualityCheck\Answer")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     */
    private $answer;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $closed = false;

    /**
     * @var bool|null
     * @Assert\NotNull
     * @Assert\Type("bool")
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $completed;

    /**
     * @var string|null
     * @Assert\Length(max="1024")
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private $note;

    /**
     * @var ActionPlan|null
     * @ORM\OneToOne(targetEntity="App\Entity\QualityCircle\ActionPlan", mappedBy="todoItem")
     */
    private $actionPlan;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return ToDoItem
     */
    public function setId(?int $id): ToDoItem
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return ToDo
     */
    public function getTodo(): ToDo
    {
        return $this->todo;
    }

    /**
     * @param ToDo $todo
     * @return ToDoItem
     */
    public function setTodo(ToDo $todo): ToDoItem
    {
        $this->todo = $todo;
        return $this;
    }

    /**
     * @return Answer
     */
    public function getAnswer(): Answer
    {
        return $this->answer;
    }

    /**
     * @param Answer $answer
     * @return ToDoItem
     */
    public function setAnswer(Answer $answer): ToDoItem
    {
        $this->answer = $answer;
        return $this;
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * @param bool $closed
     * @return ToDoItem
     */
    public function setClosed(bool $closed): ToDoItem
    {
        $this->closed = $closed;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isCompleted(): ?bool
    {
        return $this->completed;
    }

    /**
     * @param bool|null $completed
     * @return ToDoItem
     */
    public function setCompleted(?bool $completed): ToDoItem
    {
        $this->completed = $completed;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @param string|null $note
     * @return ToDoItem
     */
    public function setNote(?string $note): ToDoItem
    {
        $this->note = $note;
        return $this;
    }

    /**
     * @return ActionPlan|null
     */
    public function getActionPlan(): ?ActionPlan
    {
        return $this->actionPlan;
    }

    /**
     * @param ActionPlan|null $actionPlan
     * @return ToDoItem
     */
    public function setActionPlan(?ActionPlan $actionPlan): ToDoItem
    {
        $this->actionPlan = $actionPlan;
        return $this;
    }

    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId()
        ];
    }
}
