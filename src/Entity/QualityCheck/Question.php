<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 28.03.19
 * Time: 14:37
 */

namespace App\Entity\QualityCheck;

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Question Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Table(uniqueConstraints={
 *   @UniqueConstraint(columns={"category_id", "question"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\QuestionRepository")
 * @IgnoreAnnotation("phpcsSuppress")
 */
class Question implements \JsonSerializable
{
    public const TYPE_NEEDED = "needed";
    public const TYPE_NOT_NEEDED = "not_needed";
    /**
     *
     * @var int
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
     * @ORM\Column(type="string", length=190)
     */
    private $question;

    /**
     *
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    private $sustainable = false;

    /**
     * @var string[] Zusätzliche beschreibende Flags für die Questions
     * @ORM\Column(type="json", nullable=true)
     */
    private $flags = null;

    /**
     *
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    private $miniCheck = false;


    /**
     * @var string|null
     * @Assert\Length(max="512")
     * @ORM\Column(type="string", length=512, nullable=true)
     */
    private $miniCheckInfo;

    /**
     * @var int
     * @ORM\Column(type="smallint", nullable=false, name="`order`")
     */
    private $order;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(name="`type`", type="string", length=50, nullable=false)
     */
    private $type = self::TYPE_NOT_NEEDED;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $masterDataQuestion;

    /**
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\QualityCheck\Category", inversedBy="questions")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $category;

    /**
     * @var Question
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\QualityCheck\Question")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $previous;

    /**
     * @var Ideabox[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\QualityCheck\Ideabox", mappedBy="question", orphanRemoval=true, indexBy="id")
     * @ORM\OrderBy({"order":"ASC"})
     */
    private $ideaboxes;

    /**
     * @var Formula|null
     * @ORM\OneToOne(targetEntity="\App\Entity\QualityCheck\Formula", mappedBy="question", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $formula;

    /**
     * @var string|null
     * @Assert\Length(max="190")
     * @ORM\Column(type="string", length=190, nullable=true)
     */
    private $help;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getQuestion(): ?string
    {
        return $this->question;
    }

    /**
     * @param string $question
     * @return Question
     */
    public function setQuestion(string $question): Question
    {
        $this->question = $question;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isSustainable(): ?bool
    {
        return $this->sustainable;
    }

    /**
     * @param bool|null $sustainable
     * @return Question
     */
    public function setSustainable(?bool $sustainable): Question
    {
        $this->sustainable = $sustainable;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getFlags(): array
    {
        if (\is_null($this->flags)) {
            return [];
        }
        return $this->flags;
    }

    /**
     * @param string[] $flags
     */
    public function setFlags(array $flags): void
    {
        $this->flags = $flags;
    }

    /**
     * Prüft ob eine Frage ein bestimmtes Flag hat
     * @param string $flag
     * @return bool
     */
    public function hasFlag(string $flag): bool
    {
        if (\is_null($this->flags)) {
            return false;
        }
        return isset($this->flags[$flag]) && $this->flags[$flag] === true;
    }

    /**
     * Prüft ob eine Frage alle angegebenen Flags erfüllt (AND-Logik)
     * @param array $flags Array von Flags die erfüllt sein müssen [flagName => true]
     * @return bool True wenn alle Flags erfüllt sind
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     */
    public function matchesFlags(array $flags = []): bool
    {
        // Wenn keine Flags gesetzt sind, alle Fragen einschließen
        if (empty($flags)) {
            return true;
        }

        // Prüfe Flags - alle müssen erfüllt sein (AND-Logik)
        foreach ($flags as $flag => $value) {
            if ($value && ! $this->isFlagEqual($flag, true)) {
                return false; // Ein Flag nicht erfüllt = ausschließen
            }
        }

        return true; // Alle Flags erfüllt
    }

    /**
     * Prüft ob eine Frage ein bestimmtes Flag mit einem bestimmten Wert hat
     * @param string $flag
     * @param $value
     * @return bool
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     */
    public function isFlagEqual(string $flag, bool $value = true): bool
    {
        if (\is_null($this->flags)) {
            return false;
        }
        return isset($this->flags[$flag]) && $this->flags[$flag] === $value;
    }

    /**
     * Setzt ein Flag für eine Frage
     * @param string $flag
     * @param bool $value
     * @return Question
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     */
    public function setFlag(string $flag, bool $value = true): Question
    {
        if (\is_null($this->flags)) {
            $this->flags = [];
        }
        $this->flags[$flag] = $value;
        return $this;
    }

    /**
     * Entfernt ein Flag von einer Frage
     * @param string $flag
     * @return Question
     */
    public function removeFlag(string $flag): Question
    {
        if (! \is_null($this->flags) && isset($this->flags[$flag])) {
            unset($this->flags[$flag]);
        }
        return $this;
    }

    public function isMiniCheck(): ?bool
    {
        return $this->miniCheck;
    }

    public function setMiniCheck(?bool $miniCheck): Question
    {
        $this->miniCheck = $miniCheck;
        return $this;
    }

    public function getMiniCheckInfo(): ?string
    {
        return $this->miniCheckInfo;
    }

    public function setMiniCheckInfo(?string $miniCheckInfo): Question
    {
        $this->miniCheckInfo = $miniCheckInfo;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Question
     */
    public function setType(string $type): Question
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMasterDataQuestion(): ?string
    {
        return $this->masterDataQuestion;
    }

    /**
     * @param string|null $masterDataQuestion
     * @return Question
     */
    public function setMasterDataQuestion(?string $masterDataQuestion): Question
    {
        $this->masterDataQuestion = $masterDataQuestion;
        return $this;
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * @param int $order
     * @return Question
     */
    public function setOrder(int $order): Question
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return Question
     */
    public function getPrevious(): Question
    {
        return $this->previous;
    }

    /**
     * @param Question $previous
     * @return Question
     */
    public function setPrevious(Question $previous): Question
    {
        $this->previous = $previous;
        return $this;
    }

    /**
     * @return Category
     */
    public function getCategory(): Category
    {
        return $this->category;
    }

    /**
     * @param Category $category
     * @return Question
     */
    public function setCategory(Category $category): Question
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return Formula|null
     */
    public function getFormula(): ?Formula
    {
        return $this->formula;
    }

    /**
     * @param Formula|null $formula
     * @return Question
     */
    public function setFormula(?Formula $formula): Question
    {
        if (! \is_null($formula)) {
            $formula->setQuestion($this);
        }
        $this->formula = $formula;
        return $this;
    }

    /**
     * @return Ideabox[]|ArrayCollection
     */
    public function getIdeaboxes()
    {
        return $this->ideaboxes;
    }

    /**
     * @param Ideabox[]|ArrayCollection $ideaboxes
     * @return Question
     */
    public function setIdeaboxes($ideaboxes): Question
    {
        $this->ideaboxes = $ideaboxes;
        return $this;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function setHelp(?string $help): Question
    {
        $this->help = $help;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFirst(): bool
    {
        return $this->getOrder() === 1;
    }

    /**
     * @return bool
     */
    public function isLast(): bool
    {
        return $this->getCategory()->getQuestions()->count() === $this->getOrder();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->question;
    }


    /**
     * Reorders Ideaboxes
     */
    public function reorderIdeaboxes(): void
    {
        $order = 1;
        foreach ($this->getIdeaboxes() as $ideabox) {
            $ideabox->setOrder($order);
            $order++;
        }
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     * @return array
     */
    public function jsonSerialize(): array
    {
        // Erstelle flags Array mit allen aktiven Flags
        $activeFlags = [];
        $flagsData = $this->getFlags();
        if ($flagsData) {
            foreach ($flagsData as $flagName => $value) {
                if ($value === true) {
                    $activeFlags[$flagName] = true;
                }
            }
        }

        return [
            'id' => $this->getId(),
            'question' => $this->getQuestion(),
            'flags' => $activeFlags, // Nur noch ein flags Array - fertig!
            'order' => $this->getOrder(),
            'first' => $this->isFirst(),
            'last' => $this->isLast(),
            'type' => $this->getType(),
            'ideaboxes' => $this->getIdeaboxes()->count(),
            'category_name' => $this->getCategory()->getName(),
            'parent_category_name' => $this->getCategory()->getParent() ? $this->getCategory()->getParent()->getName() : null
        ];
    }
}
