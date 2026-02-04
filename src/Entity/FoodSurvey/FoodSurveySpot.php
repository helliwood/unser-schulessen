<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 11.10.22
 * Time: 11:37
 */

namespace App\Entity\FoodSurvey;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * FoodSurveySpot Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Entity()
 * @ORM\Table(name="food_survey_spot")
 */
class FoodSurveySpot implements \JsonSerializable
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    private ?int $id;

    /**
     * @var FoodSurvey|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\FoodSurvey\FoodSurvey", inversedBy="spots")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     **/
    private ?FoodSurvey $foodSurvey;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    private ?string $name = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $data = null;

    /**
     * @var int
     * @ORM\Column(type="smallint", nullable=false, name="`order`")
     */
    private int $order = 1;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=false)
     */
    private ?DateTime $createdAt;

    /**
     * @var ArrayCollection|Collection|FoodSurveySpotAnswer[]|null
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\FoodSurvey\FoodSurveySpotAnswer", cascade={"persist"}, mappedBy="foodSurveySpot")
     * @ORM\OrderBy({"answer": "ASC"})
     */
    private ?Collection $answers;

    /**
     * Survey constructor.
     */
    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->answers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): FoodSurveySpot
    {
        $this->id = $id;
        return $this;
    }

    public function getFoodSurvey(): ?FoodSurvey
    {
        return $this->foodSurvey;
    }

    public function setFoodSurvey(?FoodSurvey $foodSurvey): FoodSurveySpot
    {
        $this->foodSurvey = $foodSurvey;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): FoodSurveySpot
    {
        $this->name = $name;
        return $this;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(?string $data): FoodSurveySpot
    {
        $this->data = $data;
        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): FoodSurveySpot
    {
        $this->order = $order;
        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): FoodSurveySpot
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getAnswers(): ?Collection
    {
        return $this->answers;
    }

    public function setAnswers(?Collection $answers): FoodSurveySpot
    {
        $this->answers = $answers;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getStats(): array
    {
        $result = [
            FoodSurveySpotAnswer::STATE_LABELS[FoodSurveySpotAnswer::ANSWER_NO_ANSWER] => 0,
            FoodSurveySpotAnswer::STATE_LABELS[FoodSurveySpotAnswer::ANSWER_BAD] => 0,
            FoodSurveySpotAnswer::STATE_LABELS[FoodSurveySpotAnswer::ANSWER_GOOD] => 0,
        ];

        foreach ($this->answers as $answer) {
            if (! isset($result[FoodSurveySpotAnswer::STATE_LABELS[$answer->getAnswer()]])) {
                $result[FoodSurveySpotAnswer::STATE_LABELS[$answer->getAnswer()]] = 1;
            } else {
                $result[FoodSurveySpotAnswer::STATE_LABELS[$answer->getAnswer()]]++;
            }
        }

        return $result;
    }

    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'data' => $this->getData(),
            'order' => $this->getOrder(),
            'createdAt' => $this->getCreatedAt()
        ];
    }
}
