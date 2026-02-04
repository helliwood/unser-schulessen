<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 28.03.19
 * Time: 10:37
 */

namespace App\Entity;

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Address Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Entity
 * @IgnoreAnnotation("phpcsSuppress")
 */
class Address implements \JsonSerializable
{
    public const DISTRICTS_BY = [
        'OBay.' => 'Oberbayern',
        'NBay.' => 'Niederbayern',
        'OPf.' => 'Oberpfalz',
        'OFr.' => 'Oberfranken',
        'Mfr.' => 'Mittelfranken',
        'NFr.' => 'Niederfranken',
        'UFr.' => 'Unterfranken',
        'Schw.' => 'Schwaben',
    ];

    /**
     *
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    protected $id;

    /**
     *
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $street;

    /**
     *
     * @var string
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    protected $postalcode;

    /**
     *
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $city;

    /**
     * @var string
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    protected $district;

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
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @param string|null $street
     * @return Address
     */
    public function setStreet(?string $street): Address
    {
        $this->street = $street;
        return $this;
    }

    /**
     * @return string
     */
    public function getPostalcode(): ?string
    {
        return $this->postalcode;
    }

    /**
     * @param string|null $postalcode
     * @return Address
     */
    public function setPostalcode(?string $postalcode): Address
    {
        $this->postalcode = $postalcode;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string|null $city
     * @return Address
     */
    public function setCity(?string $city): Address
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string
     */
    public function getDistrict(): ?string
    {
        return $this->district;
    }

    /**
     * @param ?string $district
     * @return $this
     */
    public function setDistrict(?string $district): Address
    {
        $this->district = $district;
        return $this;
    }


    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getStreet() . "\n" . $this->getPostalcode() . " " . $this->getCity() . " " .
            ($this->getDistrict() ? "\n" . self::DISTRICTS_BY[$this->getDistrict()] : "");
    }


    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    public function jsonSerialize()
    {
        $district = null;
        if ($this->getDistrict()) {
            $district = $this->getDistrict();
        }
        return [
            'id' => $this->getId(),
            'city' => $this->getCity(),
            'postalcode' => $this->getPostalcode(),
            'street' => $this->getStreet(),
            'district' => $district,
        ];
    }
}
