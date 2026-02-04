<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 06.06.19
 * Time: 10:10
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SchoolYear Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Entity(repositoryClass="App\Repository\SchoolYearRepository")
 */
class SchoolYear
{
    /**
     *
     * @var string|null
     * @ORM\Id
     * @ORM\Column(type="string", nullable=false, length=4)
     */
    protected $year;

    /**
     *
     * @var string|null
     * @ORM\Column(type="string", nullable=false)
     */
    protected $label;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="date", nullable=false)
     */
    protected $periodBegin;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="date", nullable=false)
     */
    protected $periodEnd;

    /**
     * @return string|null
     */
    public function getYear(): ?string
    {
        return $this->year;
    }

    /**
     * @param string|null $year
     * @return SchoolYear
     */
    public function setYear(?string $year): SchoolYear
    {
        $this->year = $year;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string|null $label
     * @return SchoolYear
     */
    public function setLabel(?string $label): SchoolYear
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getPeriodBegin(): ?\DateTime
    {
        return $this->periodBegin;
    }

    /**
     * @param \DateTime|null $periodBegin
     * @return SchoolYear
     */
    public function setPeriodBegin(?\DateTime $periodBegin): SchoolYear
    {
        $this->periodBegin = $periodBegin;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getPeriodEnd(): ?\DateTime
    {
        return $this->periodEnd;
    }

    /**
     * @param \DateTime|null $periodEnd
     * @return SchoolYear
     */
    public function setPeriodEnd(?\DateTime $periodEnd): SchoolYear
    {
        $this->periodEnd = $periodEnd;
        return $this;
    }
}
