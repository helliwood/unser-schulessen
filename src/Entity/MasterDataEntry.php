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
 * MasterData MasterDataEntry
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Entity(repositoryClass="App\Repository\MasterDataEntryRepository")
 */
class MasterDataEntry
{

    /**
     * @var MasterData|null
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="\App\Entity\MasterData", cascade={"persist"}, fetch="EAGER", inversedBy="entries")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $masterData;

    /**
     *
     * @var string|null
     * @ORM\Id
     * @ORM\Column(type="string", length=190, nullable=false)
     */
    protected $step;

    /**
     *
     * @var string|null
     * @ORM\Id
     * @ORM\Column(name="`key`", length=190, type="string", nullable=false)
     */
    protected $key;

    /**
     *
     * @var string|null
     * @ORM\Column(type="string", nullable=true, length=2048)
     */
    protected $value;

    /**
     * @return MasterData|null
     */
    public function getMasterData(): ?MasterData
    {
        return $this->masterData;
    }

    /**
     * @param MasterData|null $masterData
     * @return MasterDataEntry
     */
    public function setMasterData(?MasterData $masterData): MasterDataEntry
    {
        $this->masterData = $masterData;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getStep(): ?string
    {
        return $this->step;
    }

    /**
     * @param string|null $step
     * @return MasterDataEntry
     */
    public function setStep(?string $step): MasterDataEntry
    {
        $this->step = $step;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @param string|null $key
     * @return MasterDataEntry
     */
    public function setKey(?string $key): MasterDataEntry
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return string|object|null
     */
    public function getValue()
    {
        return \json_decode($this->value);
    }

    /**
     * @param string|object|null $value
     * @return MasterDataEntry
     */
    public function setValue($value = null): MasterDataEntry
    {
        $this->value = ! \is_null($value)
            ? \json_encode($value)
            : null;

        return $this;
    }
}
