<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WarehouseZoneRepository")
 * @ORM\Table(name="warehouse_zones", uniqueConstraints={@ORM\UniqueConstraint(name="idx_warehouse_zone_external_unique", columns={"external_id", "user_id"}, options={"where": "external_id IS NOT NULL"})})
 */
class WarehouseZone extends AbstractApiEntity
{
    const MAX_NESTED_LEVEL = 100;

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
     * @var string
     *
     * @ORM\Column(type="string", nullable=true, options={"default" : ""})
     */
    protected $nfc = '';

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true, options={"default" : ""})
     */
    protected $barcode = '';

    /**
     * @var Warehouse
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Warehouse", inversedBy="zones")
     * @ORM\JoinColumn(name="warehouse_id", referencedColumnName="id")
     */
    protected $warehouse;

    /**
     * @var StockHistory
     *
     * @ORM\OneToMany(targetEntity="App\Entity\StockHistory", mappedBy="warehouseZone", fetch="EXTRA_LAZY")
     */
    protected $stockHistories;

    /**
     * @var StockRevision
     *
     * @ORM\OneToMany(targetEntity="App\Entity\StockRevision", mappedBy="warehouseZone", fetch="EXTRA_LAZY")
     */
    protected $stockRevisions;

    /**
     * One WarehouseZone has Many WarehouseZones.
     * @ORM\OneToMany(targetEntity="WarehouseZone", mappedBy="parent", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $children;

    /**
     * Many WarehouseZones have One WarehouseZone.
     * @ORM\ManyToOne(targetEntity="WarehouseZone", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true, options={"default" : 0})
     */
    protected $width = 0;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true, options={"default" : 0})
     */
    protected $height = 0;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true, options={"default" : 0})
     */
    protected $depth = 0;

    public function __construct()
    {
        $this->children       = new ArrayCollection();
        $this->stockHistories = new ArrayCollection();
        $this->stockRevisions = new ArrayCollection();
        $this->createdAt      = new \DateTime();
        $this->updatedAt      = new \DateTime();
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
            'path'        => $this->getPath($this),
            'description' => $this->getDescription(),
            'code'        => $this->getCode(),
            'nfc'         => $this->getNfc(),
            'barcode'     => $this->getBarcode(),
            'warehouseId' => $this->getWarehouse()->getId()->__toString(),
            'createdAt'   => $this->getCreatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'updatedAt'   => $this->getUpdatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'width'       => $this->getWidth(),
            'height'      => $this->getHeight(),
            'depth'       => $this->getDepth(),
            'parent'      => $this->getParent() ? $this->getParent()->getId()->__toString() : null,
            'children'    => [],
        ];

        /** @var WarehouseZone $child */
        foreach ($this->getChildren() as $child) {
            $fields['children'][] = $child->getApiFields();
        }

        return $fields;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        if (!$this->getWarehouse()) {
            return 'n/a';
        }

        return $this->getTitle() . ' (' . $this->getWarehouse()->getTitle() . ')';
    }

    /**
     * @param WarehouseZone|null $zone
     * @return string
     */
    public function getPath(WarehouseZone $zone = null) : string
    {
        if (!$zone) {
            $zone = $this;
        }

        $zoneTitle = $zone->getTitle();

        if ($zone->getParent() instanceof WarehouseZone) {
            $zoneTitle = $this->getPath($zone->getParent()) . ' -> ' . $zoneTitle;
        }

        return $zoneTitle;
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('title', new Assert\NotBlank());
        $metadata->addPropertyConstraint('description', new Assert\Optional());
        $metadata->addPropertyConstraint('code', new Assert\Optional());
        $metadata->addPropertyConstraint('nfc', new Assert\Optional());
        $metadata->addPropertyConstraint('barcode', new Assert\Optional());
        $metadata->addPropertyConstraint('userId', new Assert\NotNull());
        $metadata->addPropertyConstraint('warehouse', new Assert\NotNull());
        $metadata->addPropertyConstraint('parent', new Assert\Optional());
        $metadata->addPropertyConstraint('width', new Assert\Optional());
        $metadata->addPropertyConstraint('height', new Assert\Optional());
        $metadata->addPropertyConstraint('depth', new Assert\Optional());

        $metadata->addConstraint(new UniqueEntity([
            'fields'     => [
                'externalId',
                'userId',
            ],
            'ignoreNull' => true,
            'message'    => 'Such External ID already registered for this User.',
        ]));

        $metadata->addGetterConstraint('loop', new Assert\IsFalse([
            'message' => 'Parent zone leads to loop. Please choose another zone.',
        ]));
    }

    /**
     * @param WarehouseZone|null $initialEntity
     * @param int $currentLevel
     * @return bool
     */
    public function hasLoop(WarehouseZone $initialEntity = null, $currentLevel = 0)
    {
        if ($initialEntity && $initialEntity->getId()->__toString() === $this->getId()->__toString()) {
            return true;
        }

        if ($currentLevel > self::MAX_NESTED_LEVEL) {
            return true;
        }

        if ($this->getParent() instanceof WarehouseZone) {
            return $this->getParent()->hasLoop($this, ++$currentLevel);
        }

        return $currentLevel > self::MAX_NESTED_LEVEL;
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
     * @return WarehouseZone
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
     * @return WarehouseZone
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
     * Set code
     *
     * @param string $code
     *
     * @return WarehouseZone
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
     * Set warehouse
     *
     * @param \App\Entity\Warehouse $warehouse
     *
     * @return WarehouseZone
     */
    public function setWarehouse(\App\Entity\Warehouse $warehouse = null)
    {
        $this->warehouse = $warehouse;

        return $this;
    }

    /**
     * Get warehouse
     *
     * @return \App\Entity\Warehouse
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }

    /**
     * Add stockHistory
     *
     * @param \App\Entity\StockHistory $stockHistory
     *
     * @return WarehouseZone
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
     * @return WarehouseZone
     */
    public function setUserId(\Ramsey\Uuid\Uuid $userId): WarehouseZone
    {
        $this->userId = $userId;

        return $this;
    }


    /**
     * Set externalId
     *
     * @param string $externalId
     *
     * @return WarehouseZone
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

    /**
     * Add child
     *
     * @param \App\Entity\WarehouseZone $child
     *
     * @return WarehouseZone
     */
    public function addChild(\App\Entity\WarehouseZone $child)
    {
        $child->setParent($this);
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \App\Entity\WarehouseZone $child
     */
    public function removeChild(\App\Entity\WarehouseZone $child)
    {
        $child->setParent(null);
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param \App\Entity\WarehouseZone $parent
     *
     * @return WarehouseZone
     */
    public function setParent(\App\Entity\WarehouseZone $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \App\Entity\WarehouseZone
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set nfc
     *
     * @param string $nfc
     *
     * @return WarehouseZone
     */
    public function setNfc($nfc)
    {
        $this->nfc = $nfc;

        return $this;
    }

    /**
     * Get nfc
     *
     * @return string
     */
    public function getNfc()
    {
        return $this->nfc;
    }

    /**
     * Set barcode
     *
     * @param string $barcode
     *
     * @return WarehouseZone
     */
    public function setBarcode($barcode)
    {
        $this->barcode = $barcode;

        return $this;
    }

    /**
     * Get barcode
     *
     * @return string
     */
    public function getBarcode()
    {
        return $this->barcode;
    }

    /**
     * Set width.
     *
     * @param float|null $width
     *
     * @return WarehouseZone
     */
    public function setWidth($width = null)
    {
        $this->width = (float) $width;

        return $this;
    }

    /**
     * Get width.
     *
     * @return float|null
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height.
     *
     * @param float|null $height
     *
     * @return WarehouseZone
     */
    public function setHeight($height = null)
    {
        $this->height = (float) $height;

        return $this;
    }

    /**
     * Get height.
     *
     * @return float|null
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set depth.
     *
     * @param float|null $depth
     *
     * @return WarehouseZone
     */
    public function setDepth($depth = null)
    {
        $this->depth = (float) $depth;

        return $this;
    }

    /**
     * Get depth.
     *
     * @return float|null
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Add stockRevision.
     *
     * @param \App\Entity\StockRevision $stockRevision
     *
     * @return WarehouseZone
     */
    public function addStockRevision(\App\Entity\StockRevision $stockRevision)
    {
        $this->stockRevisions[] = $stockRevision;

        return $this;
    }

    /**
     * Remove stockRevision.
     *
     * @param \App\Entity\StockRevision $stockRevision
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeStockRevision(\App\Entity\StockRevision $stockRevision)
    {
        return $this->stockRevisions->removeElement($stockRevision);
    }

    /**
     * Get stockRevisions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStockRevisions()
    {
        return $this->stockRevisions;
    }
}
