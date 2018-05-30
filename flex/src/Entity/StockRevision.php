<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * StockRevision
 *
 * @ORM\Table(name="stock_revision")
 * @ORM\Entity(repositoryClass="App\Repository\StockRevisionRepository")
 */
class StockRevision extends AbstractApiEntity
{
    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableEntity;

    const STATE_PLANNED       = 'planned';
    const STATE_RECALCULATING = 'recalculating';
    const STATE_FINISHED      = 'finished';

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
     * @var \Ramsey\Uuid\Uuid
     *
     * @ORM\Column(name="user_id", type="uuid")
     */
    protected $userId;

    /**
     * @var ProductVariant
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\ProductVariant", inversedBy="stockRevisions")
     * @ORM\JoinColumn(name="variant_id", referencedColumnName="id", nullable=false)
     */
    protected $variant;

    /**
     * @var float
     *
     * @ORM\Column(name="qty", type="float", nullable=true)
     */
    protected $qty;

    /**
     * @var Unit
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Unit")
     * @ORM\JoinColumn(name="unit_id", referencedColumnName="id", nullable=true)
     */
    protected $unit;

    /**
     * @var float
     *
     * @ORM\Column(name="current_qty", type="float", nullable=true)
     */
    protected $currentQty;

    /**
     * @var Unit
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Unit")
     * @ORM\JoinColumn(name="unit_id", referencedColumnName="id", nullable=true)
     */
    protected $defaultUnit;

    /**
     * @var StockHistory
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\StockHistory", inversedBy="stockRevisions")
     * @ORM\JoinColumn(name="origin_stock_history_id", referencedColumnName="id", nullable=true)
     */
    protected $originStockHistory;

    /**
     * @var WarehouseZone
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\WarehouseZone", inversedBy="stockRevisions")
     * @ORM\JoinColumn(name="warehouse_zone_id", referencedColumnName="id", nullable=false)
     */
    protected $warehouseZone;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $state = self::STATE_PLANNED;

    /**
     * @var \DateTime
     * @ORM\Column(name="planned_on_date", type="datetime", nullable=true)
     */
    protected $plannedOnDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="recalculated_at", type="datetime", nullable=true)
     */
    protected $recalculatedAt;

    public function __construct()
    {
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
            'id'                   => $this->getId()->__toString(),
            'state'                => $this->getState(),
            'title'                => $this->getTitle(),
            'qty'                  => $this->getQty(),
            'variantId'            => $this->getVariant()->getId()->__toString(),
            'productId'            => $this->getVariant()->getProduct()->getId()->__toString(),
            'warehouseId'          => $this->getWarehouseZone()->getWarehouse()->getId()->__toString(),
            'warehouseZoneId'      => $this->getWarehouseZone()->getId()->__toString(),
            'originStockHistoryId' => $this->getOriginStockHistory() ? $this->getOriginStockHistory()->getId()->__toString() : '',
            'unitId'               => $this->getUnit() ? $this->getUnit()->getId()->__toString() : '',
            'unitDescriptor'       => $this->getUnit() ? $this->getUnit()->getDescriptor() : '',
            'createdAt'            => $this->getCreatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'updatedAt'            => $this->getUpdatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'plannedOnDate'        => $this->getPlannedOnDate() ? $this->getPlannedOnDate()->format(self::DEFAULT_DATE_FORMAT) : '',
            'recalculatedAt'       => $this->getRecalculatedAt() ? $this->getRecalculatedAt()->format(self::DEFAULT_DATE_FORMAT) : '',
        ];

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

        $descriptor = $this->getVariant()->getDescriptor();
        if ($this->getQty() !== null) {
            $descriptor .= ' ' . ($this->getQty() > 0 ? '=' : '=') . $this->getQty() . ' ' . $this->getUnit()->getShortForm();
        }

        return $descriptor;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->__toString();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('warehouseZone', new Assert\NotNull());
        $metadata->addPropertyConstraint('variant', new Assert\NotNull());
        $metadata->addPropertyConstraint('userId', new Assert\NotNull());

//        $metadata->addPropertyConstraint('unit', new Assert\NotNull());

//        $metadata->addPropertyConstraint('defaultUnit', new Assert\NotNull());
//        $metadata->addPropertyConstraint('currentQty', new Assert\NotBlank());

//        $metadata->addPropertyConstraint('qty', new Assert\NotBlank());
//        $metadata->addPropertyConstraint('qty', new Assert\GreaterThanOrEqual(0));
//        $metadata->addPropertyConstraint('qty', new Assert\Type([
//            'type'    => 'numeric',
//            'message' => 'The quantity {{ value }} is not a valid {{ type }}.',
//        ]));
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
     * Set qty.
     *
     * @param float $qty
     *
     * @return StockRevision
     */
    public function setQty($qty)
    {
        $this->qty = (float) $qty;

        return $this;
    }

    /**
     * Get qty.
     *
     * @return float
     */
    public function getQty()
    {
        return $this->qty;
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
     * @return StockRevision
     */
    public function setUserId(Uuid $userId): StockRevision
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Set variant.
     *
     * @param \App\Entity\ProductVariant $variant
     *
     * @return StockRevision
     */
    public function setVariant(\App\Entity\ProductVariant $variant)
    {
        $this->variant = $variant;

        return $this;
    }

    /**
     * Get variant.
     *
     * @return \App\Entity\ProductVariant
     */
    public function getVariant()
    {
        return $this->variant;
    }

    /**
     * Set unit.
     *
     * @param \App\Entity\Unit $unit
     *
     * @return StockRevision
     */
    public function setUnit(\App\Entity\Unit $unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Get unit.
     *
     * @return \App\Entity\Unit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Set originStockHistory.
     *
     * @param \App\Entity\StockHistory $originStockHistory
     *
     * @return StockRevision
     */
    public function setOriginStockHistory(\App\Entity\StockHistory $originStockHistory)
    {
        $this->originStockHistory = $originStockHistory;

        return $this;
    }

    /**
     * Get originStockHistory.
     *
     * @return \App\Entity\StockHistory
     */
    public function getOriginStockHistory()
    {
        return $this->originStockHistory;
    }

    /**
     * Set warehouseZone.
     *
     * @param \App\Entity\WarehouseZone $warehouseZone
     *
     * @return StockRevision
     */
    public function setWarehouseZone(\App\Entity\WarehouseZone $warehouseZone)
    {
        $this->warehouseZone = $warehouseZone;

        return $this;
    }

    /**
     * Get warehouseZone.
     *
     * @return \App\Entity\WarehouseZone
     */
    public function getWarehouseZone()
    {
        return $this->warehouseZone;
    }

    /**
     * Set currentQty.
     *
     * @param float $currentQty
     *
     * @return StockRevision
     */
    public function setCurrentQty($currentQty)
    {
        $this->currentQty = (float) $currentQty;

        return $this;
    }

    /**
     * Get currentQty.
     *
     * @return float
     */
    public function getCurrentQty()
    {
        return $this->currentQty;
    }

    /**
     * Set defaultUnit.
     *
     * @param \App\Entity\Unit $defaultUnit
     *
     * @return StockRevision
     */
    public function setDefaultUnit(\App\Entity\Unit $defaultUnit)
    {
        $this->defaultUnit = $defaultUnit;

        return $this;
    }

    /**
     * Get defaultUnit.
     *
     * @return \App\Entity\Unit
     */
    public function getDefaultUnit()
    {
        return $this->defaultUnit;
    }

    /**
     * Set state.
     *
     * @param string $state
     *
     * @return StockRevision
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set plannedOnDate.
     *
     * @param \DateTime $plannedOnDate
     *
     * @return StockRevision
     */
    public function setPlannedOnDate($plannedOnDate)
    {
        $this->plannedOnDate = $plannedOnDate;

        return $this;
    }

    /**
     * Get plannedOnDate.
     *
     * @return \DateTime
     */
    public function getPlannedOnDate()
    {
        return $this->plannedOnDate;
    }

    /**
     * Set recalculatedAt.
     *
     * @param \DateTime $recalculatedAt
     *
     * @return StockRevision
     */
    public function setRecalculatedAt($recalculatedAt)
    {
        $this->recalculatedAt = $recalculatedAt;

        return $this;
    }

    /**
     * Get recalculatedAt.
     *
     * @return \DateTime
     */
    public function getRecalculatedAt()
    {
        return $this->recalculatedAt;
    }
}
