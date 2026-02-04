<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 29.03.19
 * Time: 10:29
 */

namespace App\Entity\QualityCheck;

use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Questionnaire Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Entity(repositoryClass="App\Repository\QuestionnaireRepository")
 * @UniqueEntity("name")
 */
class Questionnaire implements \JsonSerializable
{

    public const STATE_NEW = 0;
    public const STATE_ACTIVE = 1;
    public const STATE_ARCHIVED = 2;

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
     * @Assert\Length(max="190")
     * @ORM\Column(type="string", length=190, nullable=false, unique=true)
     */
    private $name;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $date;

    /**
     * @var Category[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="\App\Entity\QualityCheck\Category", mappedBy="questionnaire", cascade={"persist"}, orphanRemoval=true)
     * @ORM\OrderBy({"order":"ASC"})
     */
    private $categories;

    /**
     * @var User|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\User")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     */
    private $createdBy;

    /**
     * @var Questionnaire|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\QualityCheck\Questionnaire")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $basedOn;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", nullable=false, options={"default" : 0})
     */
    protected $state = self::STATE_NEW;

    /**
     * Questionnaire constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->date = new \DateTime();
        $this->categories = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return Result
     */
    public function setId(?int $id): Questionnaire
    {
        $this->id = $id;
        return $this;
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
     * @return Questionnaire
     */
    public function setName(string $name): Questionnaire
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    /**
     * @param \DateTime|null $date
     * @return Result
     */
    public function setDate(?\DateTime $date): Questionnaire
    {
        $this->date = $date;
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
     * @return Result
     */
    public function setCreatedBy(?User $createdBy): Questionnaire
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @param int $state
     * @return Questionnaire
     */
    public function setState(int $state): Questionnaire
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getCategories(): ArrayCollection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('parent'))
            ->orderBy(['order' => Criteria::ASC]);

        return $this->categories->matching($criteria);
    }

    /**
     * @param ArrayCollection|Category[]|null $categories
     * @return Questionnaire
     */
    public function setCategories($categories): Questionnaire
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * @return Questionnaire|null
     */
    public function getBasedOn(): ?Questionnaire
    {
        return $this->basedOn;
    }

    /**
     * @param Questionnaire|null $basedOn
     * @return Questionnaire
     */
    public function setBasedOn(?Questionnaire $basedOn): Questionnaire
    {
        $this->basedOn = $basedOn;
        return $this;
    }

    /**
     * @param Result|null $result
     * @param array|null $flags
     * @return int
     */
    public function countQuestions(?Result $result, ?array $flags = []): int
    {
        $countQuestions = 0;
        /** @var Category $category */
        foreach ($this->getCategories() as $category) {
            $countQuestions += $category->getNumberOfQuestions($result, $flags);
        }

        return $countQuestions;
    }

    /**
     * Ordnet die Kategorien neu
     */
    public function reorderCategories(): void
    {
        $order = 1;
        $parentOrders = [];
        foreach ($this->getCategories() as $category) {
            if (\is_null($category->getParent())) {
                $category->setOrder($order);
                $order++;
            } else {
                if (! isset($parentOrders[$category->getParent()->getId()])) {
                    $parentOrders[$category->getParent()->getId()] = 1;
                }
                $category->setOrder($parentOrders[$category->getParent()->getId()]);
                $parentOrders[$category->getParent()->getId()]++;
            }
        }
    }

    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'date' => $this->getDate(),
            'categories' => $this->getCategories()->count(),
            'state' => $this->getState(),
        ];
    }

    /**
     * @return string|null
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
