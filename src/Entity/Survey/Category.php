<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 08.08.19
 * Time: 14:37
 */

namespace App\Entity\Survey;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Category Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Table(name="survey_category")
 * @ORM\Entity(repositoryClass="App\Repository\Survey\CategoryRepository")
 */
class Category implements \JsonSerializable
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
     * @Assert\Length(max="150")
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    private $name;

    /**
     *
     * @var int
     * @ORM\Column(type="smallint", nullable=false, name="`order`")
     */
    private $order;

    /**
     * @var Question[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Survey\Question", mappedBy="category", orphanRemoval=true, indexBy="id")
     * @ORM\OrderBy({"order":"ASC"})
     */
    private $questions;

    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->questions = new ArrayCollection();
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
     * @return Category
     */
    public function setName(string $name): Category
    {
        $this->name = $name;
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
     * @return Category
     */
    public function setOrder(int $order): Category
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return Question[]|ArrayCollection
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    /**
     * @param Question[]|ArrayCollection $questions
     * @return Category
     */
    public function setQuestions($questions): Category
    {
        $this->questions = $questions;
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
     * Reorders Questions
     */
    public function reorderQuestions(): void
    {
        $order = 1;
        foreach ($this->getQuestions() as $question) {
            $question->setOrder($order);
            $order++;
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
            'order' => $this->getOrder(),
            'questions' => $this->getQuestions()->count()
        ];
    }
}
