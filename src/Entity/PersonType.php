<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 08.04.19
 * Time: 12:10
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Person Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Entity()
 */
class PersonType
{
    public const TYPE_HEADMASTER = 'Schulleitung';
    public const TYPE_MEMBER = 'Verpflegungsausschuss';
    public const TYPE_FOOD_COMMISSIONER = 'Verpflegungsbeauftragte(r)';
    public const TYPE_SCHOOL_AUTHORITIES = 'Schulträger';
    public const TYPE_KITCHEN = 'Küche/Speisenanbieter';
    public const TYPE_CONSULTANT = 'Ernährungsberater';

    public const TYPE_GUEST = 'Gast';

    /**
     * @var string[]
     */
    public static $publicTypes = [
        self::TYPE_HEADMASTER,
        self::TYPE_MEMBER,
        self::TYPE_FOOD_COMMISSIONER,
        self::TYPE_SCHOOL_AUTHORITIES,
        self::TYPE_KITCHEN,
        self::TYPE_CONSULTANT,
        self::TYPE_GUEST,
    ];

    /**
     * @var string|null
     * @ORM\Id
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    protected $name;

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return PersonType
     */
    public function setName(?string $name): PersonType
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function __toString(): string
    {
        return $this->getName() ?? '';
    }
}
