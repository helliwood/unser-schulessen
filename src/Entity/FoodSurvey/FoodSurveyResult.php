<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 08.08.19
 * Time: 14:37
 */

namespace App\Entity\FoodSurvey;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * FoodSurveyResult Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Table(name="food_survey_result")
 * @ORM\Entity(repositoryClass="App\Repository\FoodSurvey\FoodSurveyRepository")
 */
class FoodSurveyResult implements \JsonSerializable
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
     * @ORM\ManyToOne(targetEntity="\App\Entity\FoodSurvey\FoodSurvey", inversedBy="results")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     **/
    private ?FoodSurvey $foodSurvey;

    /**
     * @var FoodSurveySpotAnswer[]|Collection|ArrayCollection|null
     * @ORM\OneToMany(targetEntity="App\Entity\FoodSurvey\FoodSurveySpotAnswer", mappedBy="foodSurveyResult")
     **/
    private Collection $answers;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private ?string $userAgent;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $userIp;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=false)
     */
    private ?DateTime $createdAt;

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

    public function setId(?int $id): FoodSurveyResult
    {
        $this->id = $id;
        return $this;
    }

    public function getFoodSurvey(): ?FoodSurvey
    {
        return $this->foodSurvey;
    }

    public function setFoodSurvey(?FoodSurvey $foodSurvey): FoodSurveyResult
    {
        $this->foodSurvey = $foodSurvey;
        return $this;
    }

    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function setAnswers(Collection $answers): FoodSurveyResult
    {
        $this->answers = $answers;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): FoodSurveyResult
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getUserIp(): ?string
    {
        return $this->userIp;
    }

    public function setUserIp(?string $userIp): FoodSurveyResult
    {
        $this->userIp = $userIp;
        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): FoodSurveyResult
    {
        $this->createdAt = $createdAt;
        return $this;
    }


    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
        ];
    }
}
