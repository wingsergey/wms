<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UnitEqualRepository")
 * @ORM\Table(name="unit_equals", uniqueConstraints={@ORM\UniqueConstraint(name="idx_unique_pair", columns={"sale_unit_id", "storage_unit_id"}) })
 */
class UnitEqual extends AbstractApiEntity
{
    /**
     * @var \Ramsey\Uuid\Uuid
     *
     * @ORM\Id
     * @ORM\Column(type="uuid")
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    protected $id;

    /**
     * @var Unit
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Unit")
     * @ORM\JoinColumn(name="sale_unit_id", referencedColumnName="id", nullable=false)
     */
    protected $saleUnit;

    /**
     * @var Unit
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Unit")
     * @ORM\JoinColumn(name="storage_unit_id", referencedColumnName="id", nullable=false)
     */
    protected $storageUnit;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=false)
     */
    protected $equal;

    /**
     * @param bool $lazy
     * @return array
     */
    public function getApiFields(bool $lazy = false) : array
    {
        if ($lazy) {
            return parent::getApiFields($lazy);
        }

        $fields = [
            'id'          => $this->getId()->__toString(),
            'saleUnit'    => $this->getSaleUnit(),
            'storageUnit' => $this->getStorageUnit(),
            'equal'       => $this->getEqual(),
            'title'       => $this->__toString(),
        ];

        return $fields;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        $string = 'n/a';
        if ($this->getSaleUnit() && $this->getStorageUnit()) {
            $string = '1 ' . $this->getSaleUnit()->getShortForm() . ' = ' . $this->getEqual() . ' ' . $this->getStorageUnit()->getShortForm();
        }

        if ($this->getSaleUnit()->getProduct()) {
            $string .= ' (' . $this->getSaleUnit()->getProduct()->getName() . ')';
        }

        return (string) $string;
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('equal', new Assert\NotBlank());
        $metadata->addPropertyConstraint('equal', new Assert\GreaterThan(0));

        $metadata->addPropertyConstraint('saleUnit', new Assert\NotNull());
        $metadata->addPropertyConstraint('storageUnit', new Assert\NotNull());

        $metadata->addGetterConstraint('differentUnits', new Assert\IsTrue([
            'message' => 'Please specify different units',
        ]));

        $metadata->addConstraint(new UniqueEntity([
            'fields'  => [
                'saleUnit',
                'storageUnit',
            ],
            'message' => 'Same Units pair already registered for this User.',
        ]));
    }

    /**
     * @return bool
     */
    public function isDifferentUnits() : bool
    {
        return $this->getStorageUnit()->getId() !== $this->getSaleUnit()->getId();
    }

    /************************** Auto-generated getters further ****************************/

    /**
     * @return \Ramsey\Uuid\Uuid
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set equal
     *
     * @param float $equal
     *
     * @return UnitEqual
     */
    public function setEqual($equal)
    {
        $this->equal = (float) $equal;

        return $this;
    }

    /**
     * Get equal
     *
     * @return float
     */
    public function getEqual()
    {
        return $this->equal;
    }

    /**
     * Set saleUnit
     *
     * @param \App\Entity\Unit $saleUnit
     *
     * @return UnitEqual
     */
    public function setSaleUnit(Unit $saleUnit = null)
    {
        $this->saleUnit = $saleUnit;

        return $this;
    }

    /**
     * Get saleUnit
     *
     * @return \App\Entity\Unit
     */
    public function getSaleUnit()
    {
        return $this->saleUnit;
    }

    /**
     * Set storageUnit
     *
     * @param \App\Entity\Unit $storageUnit
     *
     * @return UnitEqual
     */
    public function setStorageUnit(Unit $storageUnit = null)
    {
        $this->storageUnit = $storageUnit;

        return $this;
    }

    /**
     * Get storageUnit
     *
     * @return \App\Entity\Unit
     */
    public function getStorageUnit()
    {
        return $this->storageUnit;
    }
}
