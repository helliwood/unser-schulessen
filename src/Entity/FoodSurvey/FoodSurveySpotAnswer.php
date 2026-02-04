<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 11.10.22
 * Time: 11:37
 */

namespace App\Entity\FoodSurvey;

use Doctrine\ORM\Mapping as ORM;

/**
 * FoodSurveySpotAnswer Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Entity()
 * @ORM\Table(name="food_survey_spot_answer")
 */
class FoodSurveySpotAnswer implements \JsonSerializable
{
    public const ANSWER_GOOD = 1;
    public const ANSWER_BAD = 0;
    public const ANSWER_NO_ANSWER = -1;

    public const STATE_LABELS = [
        self::ANSWER_NO_ANSWER => 'Weiß ich nicht',
        self::ANSWER_GOOD => 'Gut',
        self::ANSWER_BAD => 'Schlecht',
    ];

    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    private ?int $id;

    /**
     * @var FoodSurveyResult|null
     * @ORM\ManyToOne(targetEntity="App\Entity\FoodSurvey\FoodSurveyResult", inversedBy="answers")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     **/
    private ?FoodSurveyResult $foodSurveyResult;


    /**
     * @var FoodSurveySpot|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\FoodSurvey\FoodSurveySpot", inversedBy="answers")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     **/
    private ?FoodSurveySpot $foodSurveySpot;

    /**
     * @var int|null
     * @ORM\Column(type="smallint", nullable=false, options={"unsigned":false})
     */
    private ?int $answer = null;

    /**
     * FoodSurveySpotAnswer constructor.
     */
    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): FoodSurveySpotAnswer
    {
        $this->id = $id;
        return $this;
    }

    public function getFoodSurveyResult(): ?FoodSurveyResult
    {
        return $this->foodSurveyResult;
    }

    public function setFoodSurveyResult(?FoodSurveyResult $foodSurveyResult): FoodSurveySpotAnswer
    {
        $this->foodSurveyResult = $foodSurveyResult;
        return $this;
    }

    public function getFoodSurveySpot(): ?FoodSurveySpot
    {
        return $this->foodSurveySpot;
    }

    public function setFoodSurveySpot(?FoodSurveySpot $foodSurveySpot): FoodSurveySpotAnswer
    {
        $this->foodSurveySpot = $foodSurveySpot;
        return $this;
    }

    public function getAnswer(): ?int
    {
        return $this->answer;
    }

    public function setAnswer(?int $answer): FoodSurveySpotAnswer
    {
        $this->answer = $answer;
        return $this;
    }

    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'answer' => $this->getAnswer(),
        ];
    }
}
