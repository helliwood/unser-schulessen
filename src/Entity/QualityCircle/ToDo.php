<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 09.08.19
 * Time: 13:37
 */

namespace App\Entity\QualityCircle;

use App\Entity\QualityCheck\Result;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ToDo-Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Entity(repositoryClass="App\Repository\QualityCircle\ToDoRepository")
 */
class ToDo implements \JsonSerializable
{
    /**
     *
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    private $id;

    /**
     *
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    private $name;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\User", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    private $createdBy;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $archived = false;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $archivedAt;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\User", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT", nullable=true)
     */
    private $archivedBy;

    /**
     * @var Result|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\QualityCheck\Result")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     **/
    private $result;

    /**
     * @var ToDoItem[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\QualityCircle\ToDoItem", mappedBy="todo", orphanRemoval=true, indexBy="id")
     */
    private $items;

    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ToDo
     */
    public function setName(string $name): ToDo
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime|null $createdAt
     * @return ToDo
     */
    public function setCreatedAt(?\DateTime $createdAt): ToDo
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return User|null
     */
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    /**
     * @param User|null $createdBy
     * @return ToDo
     */
    public function setCreatedBy(?User $createdBy): ToDo
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->archived;
    }

    /**
     * @param bool $archived
     * @return ToDo
     */
    public function setArchived(bool $archived): ToDo
    {
        $this->archived = $archived;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getArchivedAt(): ?\DateTime
    {
        return $this->archivedAt;
    }

    /**
     * @param \DateTime|null $archivedAt
     * @return ToDo
     */
    public function setArchivedAt(?\DateTime $archivedAt): ToDo
    {
        $this->archivedAt = $archivedAt;
        return $this;
    }

    /**
     * @return User|null
     */
    public function getArchivedBy(): ?User
    {
        return $this->archivedBy;
    }

    /**
     * @param User|null $archivedBy
     * @return ToDo
     */
    public function setArchivedBy(?User $archivedBy): ToDo
    {
        $this->archivedBy = $archivedBy;
        return $this;
    }

    /**
     * @return Result|null
     */
    public function getResult(): ?Result
    {
        return $this->result;
    }

    /**
     * @param Result|null $result
     * @return ToDo
     */
    public function setResult(?Result $result): ToDo
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return ToDoItem[]|ArrayCollection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param ToDoItem[]|ArrayCollection $items
     * @return ToDo
     */
    public function setItems($items): ToDo
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @return array|ActionPlan[]
     */
    public function getActionPlans(): array
    {
        $actionPlans = [];
        foreach ($this->getItems() as $item) {
            if ($item->getActionPlan()) {
                $actionPlans[] = $item->getActionPlan();
            }
        }
        return $actionPlans;
    }

    /**
     * @return int[]
     */
    public function getStats(): array
    {
        $stats = ['items' => 0, 'items_completed' => 0, 'action_plans' => 0];
        foreach ($this->getItems() as $item) {
            $stats['items']++;
            if ($item->isCompleted()) {
                $stats['items_completed']++;
            }
            if (! \is_null($item->getActionPlan())) {
                $stats['action_plans']++;
            }
        }
        return $stats;
    }

    /**
     * @return bool
     */
    public function allToDoClosed(): bool
    {
        foreach ($this->getItems() as $toDoItem) {
            if (! $toDoItem->isClosed()) {
                return false;
            }
        }
        return true;
    }


    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return \array_merge([
            'id' => $this->getId(),
            'name' => $this->getName(),
            'createdAt' => $this->getCreatedAt(),
            'createdBy' => $this->getCreatedBy()->getDisplayName(),
            'archived' => $this->isArchived(),
            'archivedAt' => $this->getArchivedAt(),
            'archivedBy' => $this->getArchivedBy() ? $this->getArchivedBy()->getDisplayName() : null
        ], $this->getStats());
    }
}
