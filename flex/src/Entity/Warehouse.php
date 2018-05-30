<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WarehouseRepository")
 * @ORM\Table(name="warehouses", uniqueConstraints={@ORM\UniqueConstraint(name="idx_warehouse_external_unique", columns={"external_id", "user_id"}, options={"where": "external_id IS NOT NULL"})})
 */
class Warehouse extends AbstractApiEntity
{
    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableEntity;

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
     * @var string
     *
     * @ORM\Column(name="external_id", type="string", nullable=true)
     */
    protected $externalId;

    /**
     * @var \Ramsey\Uuid\Uuid
     *
     * @ORM\Column(name="user_id", type="uuid")
     */
    protected $userId;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true, options={"default" : ""})
     */
    protected $description = '';

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true, options={"default" : ""})
     */
    protected $code = '';

    /**
     * @var ArrayCollection|WarehouseZone[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\WarehouseZone", mappedBy="warehouse", fetch="EXTRA_LAZY", orphanRemoval=true, cascade={"persist", "remove"})
     */
    protected $zones;

    /**
     * @var StockHistory
     *
     * @ORM\OneToMany(targetEntity="App\Entity\StockHistory", mappedBy="warehouse", fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    protected $stockHistories;

    public function __construct()
    {
        $this->zones          = new ArrayCollection();
        $this->stockHistories = new ArrayCollection();

        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

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
            'externalId'  => $this->getExternalId(),
            'title'       => $this->getTitle(),
            'description' => $this->getDescription(),
            'code'        => $this->getCode(),
            'createdAt'   => $this->getCreatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'updatedAt'   => $this->getUpdatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'zones'       => [],
        ];

        /** @var WarehouseZone $zone */
        foreach ($this->getZones() as $zone) {
            $fields['zones'][] = $zone->getApiFields($lazy);
        }

        return $fields;
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('title', new Assert\NotBlank());
        $metadata->addPropertyConstraint('description', new Assert\Optional());
        $metadata->addPropertyConstraint('code', new Assert\Optional());

        $metadata->addPropertyConstraint('userId', new Assert\NotNull());

        $metadata->addConstraint(new UniqueEntity([
            'fields'     => [
                'externalId',
                'userId',
            ],
            'ignoreNull' => true,
            'message'    => 'Such External ID already registered for this User.',
        ]));
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
     * Set title
     *
     * @param string $title
     *
     * @return Warehouse
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Warehouse
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Add zone
     *
     * @param \App\Entity\WarehouseZone $zone
     *
     * @return Warehouse
     */
    public function addZone(\App\Entity\WarehouseZone $zone)
    {
        $this->zones[] = $zone;

        return $this;
    }

    /**
     * Remove zone
     *
     * @param \App\Entity\WarehouseZone $zone
     */
    public function removeZone(\App\Entity\WarehouseZone $zone)
    {
        $this->zones->removeElement($zone);
    }

    /**
     * Get zones
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getZones()
    {
        return $this->zones;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Warehouse
     */
    public function setCode($code)
    {
        $this->code = (string) $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Add stockHistory
     *
     * @param \App\Entity\StockHistory $stockHistory
     *
     * @return Warehouse
     */
    public function addStockHistory(\App\Entity\StockHistory $stockHistory)
    {
        $this->stockHistories[] = $stockHistory;

        return $this;
    }

    /**
     * Remove stockHistory
     *
     * @param \App\Entity\StockHistory $stockHistory
     */
    public function removeStockHistory(\App\Entity\StockHistory $stockHistory)
    {
        $this->stockHistories->removeElement($stockHistory);
    }

    /**
     * Get stockHistories
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStockHistories()
    {
        return $this->stockHistories;
    }

    /**
     * @return \Ramsey\Uuid\Uuid
     */
    public function getUserId(): \Ramsey\Uuid\Uuid
    {
        return $this->userId;
    }

    /**
     * @param \Ramsey\Uuid\Uuid $userId
     * @return Warehouse
     */
    public function setUserId(\Ramsey\Uuid\Uuid $userId): Warehouse
    {
        $this->userId = $userId;

        return $this;
    }


    /**
     * Set externalId
     *
     * @param string $externalId
     *
     * @return Warehouse
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId ?: null;

        return $this;
    }

    /**
     * Get externalId
     *
     * @return string
     */
    public function getExternalId() : string
    {
        return (string) $this->externalId;
    }
}
