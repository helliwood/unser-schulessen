<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 08.08.19
 * Time: 14:37
 */

namespace App\Entity\Survey;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Question Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Table(name="survey_question", uniqueConstraints={
 *   @UniqueConstraint(columns={"category_id", "question"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\Survey\QuestionRepository")
 */
class Question implements \JsonSerializable
{
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
     * @var int
     * @ORM\Column(type="smallint", nullable=false, name="`order`")
     */
    private $order;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true, name="sustainable", options={"default":"0"})
     */
    private $sustainable = false;

    /**
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\Survey\Category", inversedBy="questions")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $category;

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
     * @return bool
     */
    public function isSustainable(): bool
    {
        return $this->sustainable;
    }

    /**
     * @param bool $sustainable
     */
    public function setSustainable(bool $sustainable): void
    {
        $this->sustainable = $sustainable;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->question;
    }


    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'question' => $this->getQuestion(),
            'sustainable' => $this->isSustainable(),
            'order' => $this->getOrder(),
            'first' => $this->isFirst(),
            'last' => $this->isLast()
        ];
    }
}
