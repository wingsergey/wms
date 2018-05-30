<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use function time;

/**
 * StockHistory
 *
 * @ORM\Table(name="stock_history", indexes={@ORM\Index(name="search_hash", columns={"full_search_hash"})}, uniqueConstraints={@ORM\UniqueConstraint(name="idx_stock_external_item_unique", columns={"external_item_id", "user_id"}, options={"where": "external_item_id IS NOT NULL"})})
 * @ORM\Entity(repositoryClass="App\Repository\StockHistoryRepository")
 *
 * @ORM\HasLifecycleCallbacks()
 */
class StockHistory extends AbstractApiEntity
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
     * @ORM\Column(name="external_item_id", type="string", nullable=true)
     */
    protected $externalItemId;

    /**
     * @var \Ramsey\Uuid\Uuid
     *
     * @ORM\Column(name="user_id", type="uuid")
     */
    protected $userId;

    /**
     * @var ProductVariant
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\ProductVariant", inversedBy="stockHistories")
     * @ORM\JoinColumn(name="variant_id", referencedColumnName="id", nullable=false)
     */
    protected $variant;

    /**
     * @var StockHistory
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\StockHistory")
     * @ORM\JoinColumn(name="origin_stock_history_id", referencedColumnName="id")
     */
    protected $originStockHistory;

    /**
     * @var float
     *
     * @ORM\Column(name="changes", type="float")
     */
    protected $changes;

    /**
     * @var integer
     *
     * @ORM\Column(name="price", type="integer", nullable=true, options={"default" : 0})
     */
    protected $price = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="full_search_hash", type="string", length=32, nullable=true)
     */
    protected $full_search_hash;

    /**
     * @var Warehouse
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Warehouse", inversedBy="stockHistories")
     * @ORM\JoinColumn(name="warehouse_id", referencedColumnName="id", nullable=false)
     */
    protected $warehouse;

    /**
     * @var WarehouseZone
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\WarehouseZone", inversedBy="stockHistories")
     * @ORM\JoinColumn(name="warehouse_zone_id", referencedColumnName="id", nullable=false)
     */
    protected $warehouseZone;

    /**
     * @var Unit
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Unit")
     * @ORM\JoinColumn(name="unit_id", referencedColumnName="id", nullable=false)
     */
    protected $unit;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiration_date", type="datetime", nullable=true)
     */
    protected $expirationDate;

    /**
     * @var ArrayCollection|StockReservation[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\StockReservation", mappedBy="originStockHistory", fetch="EXTRA_LAZY", orphanRemoval=true, cascade={"persist", "remove"})
     */
    protected $stockReservations;

    /**
     * @var ArrayCollection|StockRevision[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\StockRevision", mappedBy="originStockHistory", fetch="EXTRA_LAZY", orphanRemoval=false, cascade={"persist"})
     */
    protected $stockRevisions;

    /**
     * @var string
     */
    protected $externalCartId;

    public function __construct()
    {
        $this->createdAt         = new \DateTime();
        $this->updatedAt         = new \DateTime();
        $this->stockReservations = new ArrayCollection();
        $this->stockRevisions    = new ArrayCollection();
    }

    /**
     * @return StockHistory
     */
    public function updateHash()
    {
        $this->full_search_hash = md5($this->variant->getId()->toString() . '_' . $this->createdAt->getTimestamp() . '_' . $this->changes . '_' . $this->price . '_' . ($this->originStockHistory ? $this->originStockHistory->getId()->toString() : ''));

        return $this;
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
            'id'              => $this->getId()->__toString(),
            'externalItemId'  => $this->getExternalItemId(),
            'title'           => $this->getTitle(),
            'changes'         => $this->getChanges(),
            'price'           => $this->getPrice(),
            'variantId'       => $this->getVariant()->getId()->__toString(),
            'productId'       => $this->getVariant()->getProduct()->getId()->__toString(),
            'warehouseId'     => $this->getWarehouse()->getId()->__toString(),
            'warehouseZoneId' => $this->getWarehouseZone()->getId()->__toString(),
            'unitId'          => $this->getUnit()->getId()->__toString(),
            'unitDescriptor'  => $this->getUnit()->getDescriptor(),
            'createdAt'       => $this->getCreatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'updatedAt'       => $this->getUpdatedAt()->format(self::DEFAULT_DATE_FORMAT),
        ];

        if ($this->getWarehouseZone()) {
            $fields['warehouseZoneId'] = $this->getWarehouseZone()->getId()->__toString();
        }

        return $fields;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        if (!($this->getVariant() instanceof ProductVariant)) {
            return 'n/a';
        }

        return $this->getVariant()->getDescriptor() . ' ' . ($this->getChanges() > 0 ? '+' : '') . $this->getChanges() . ' ' . $this->getUnit()->getShortForm();
    }

    /**
     * @return string
     */
    public function getTitle() : string
    {
        return $this->__toString();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('variant', new Assert\NotNull());
        $metadata->addPropertyConstraint('userId', new Assert\NotNull());
        $metadata->addPropertyConstraint('warehouse', new Assert\NotNull());
        $metadata->addPropertyConstraint('warehouseZone', new Assert\NotNull());
        $metadata->addPropertyConstraint('unit', new Assert\NotNull());

        $metadata->addPropertyConstraint('changes', new Assert\NotBlank());
        $metadata->addPropertyConstraint('changes', new Assert\NotEqualTo(0));
        $metadata->addPropertyConstraint('changes', new Assert\Type([
            'type'    => 'numeric',
            'message' => 'The changes {{ value }} is not a valid {{ type }}.',
        ]));

        $metadata->addPropertyConstraint('price', new Assert\NotBlank());
        $metadata->addPropertyConstraint('price', new Assert\Type([
            'type'    => 'integer',
            'message' => 'The price {{ value }} is not a valid {{ type }}.',
        ]));

        $metadata->addConstraint(new UniqueEntity([
            'fields'     => [
                'externalItemId',
                'userId',
            ],
            'ignoreNull' => true,
            'message'    => 'Such External Item ID already registered for this User.',
        ]));
    }

    /************************** Auto-generated getters further ****************************/

    /**
     * Get id
     *
     * @return Uuid
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set changes
     *
     * @param float $changes
     *
     * @return StockHistory
     */
    public function setChanges($changes)
    {
        $this->changes = (float) $changes;

        return $this;
    }

    /**
     * Get changes
     *
     * @return integer
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Set price
     *
     * @param integer $price
     *
     * @return StockHistory
     */
    public function setPrice($price)
    {
        $this->price = (int) $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return integer
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set fullSearchHash
     *
     * @param string $fullSearchHash
     *
     * @return StockHistory
     */
    public function setFullSearchHash($fullSearchHash)
    {
        $this->full_search_hash = $fullSearchHash;

        return $this;
    }

    /**
     * Get fullSearchHash
     *
     * @return string
     */
    public function getFullSearchHash()
    {
        return $this->full_search_hash;
    }

    /**
     * Set variant
     *
     * @param \App\Entity\ProductVariant $variant
     *
     * @return StockHistory
     */
    public function setVariant(\App\Entity\ProductVariant $variant = null)
    {
        $this->variant = $variant;

        return $this;
    }

    /**
     * Get variant
     *
     * @return \App\Entity\ProductVariant
     */
    public function getVariant()
    {
        return $this->variant;
    }

    /**
     * Set originStockHistory
     *
     * @param \App\Entity\StockHistory $originStockHistory
     *
     * @return StockHistory
     */
    public function setOriginStockHistory(\App\Entity\StockHistory $originStockHistory = null)
    {
        $this->originStockHistory = $originStockHistory;

        return $this;
    }

    /**
     * Get originStockHistory
     *
     * @return \App\Entity\StockHistory
     */
    public function getOriginStockHistory()
    {
        return $this->originStockHistory;
    }

    /**
     * Set warehouse
     *
     * @param \App\Entity\Warehouse $warehouse
     *
     * @return StockHistory
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
     * Set warehouseZone
     *
     * @param \App\Entity\WarehouseZone $warehouseZone
     *
     * @return StockHistory
     */
    public function setWarehouseZone(\App\Entity\WarehouseZone $warehouseZone = null)
    {
        $this->warehouseZone = $warehouseZone;

        return $this;
    }

    /**
     * Get warehouseZone
     *
     * @return \App\Entity\WarehouseZone
     */
    public function getWarehouseZone()
    {
        return $this->warehouseZone;
    }

    /**
     * Set unit
     *
     * @param \App\Entity\Unit $unit
     *
     * @return StockHistory
     */
    public function setUnit(\App\Entity\Unit $unit = null)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Get unit
     *
     * @return \App\Entity\Unit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @return Uuid
     */
    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    /**
     * @param Uuid $userId
     * @return StockHistory
     */
    public function setUserId(Uuid $userId): StockHistory
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Set externalItemId
     *
     * @param string $externalItemId
     *
     * @return StockHistory
     */
    public function setExternalItemId(string $externalItemId)
    {
        $this->externalItemId = $externalItemId ?: null;

        return $this;
    }

    /**
     * Get externalItemId
     *
     * @return string
     */
    public function getExternalItemId() : string
    {
        return (string) $this->externalItemId;
    }

    /**
     * Set expirationDate
     *
     * @param \DateTime $expirationDate
     *
     * @return StockHistory
     */
    public function setExpirationDate($expirationDate)
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    /**
     * Get expirationDate
     *
     * @return \DateTime
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * Add stockReservation
     *
     * @param \App\Entity\StockReservation $stockReservation
     *
     * @return StockHistory
     */
    public function addStockReservation(\App\Entity\StockReservation $stockReservation)
    {
        $this->stockReservations[] = $stockReservation;

        return $this;
    }

    /**
     * Remove stockReservation
     *
     * @param \App\Entity\StockReservation $stockReservation
     */
    public function removeStockReservation(\App\Entity\StockReservation $stockReservation)
    {
        $this->stockReservations->removeElement($stockReservation);
    }

    /**
     * Get stockReservations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStockReservations()
    {
        return $this->stockReservations->filter(function (StockReservation $reservation) {
            return $reservation->getExpirationDate()->getTimestamp() > time();
        });
    }

    /**
     * Set externalCartId
     *
     * @param string $externalCartId
     *
     * @return StockHistory
     */
    public function setExternalCartId($externalCartId)
    {
        $this->externalCartId = $externalCartId;

        return $this;
    }

    /**
     * Get externalCartId
     *
     * @return string
     */
    public function getExternalCartId()
    {
        return $this->externalCartId;
    }

    /**
     * Add stockRevision.
     *
     * @param \App\Entity\StockRevision $stockRevision
     *
     * @return StockHistory
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
