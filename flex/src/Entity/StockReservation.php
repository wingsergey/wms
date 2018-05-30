<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * StockReservation
 *
 * @ORM\Table(name="stock_reservation")
 * @ORM\Entity(repositoryClass="App\Repository\StockReservationRepository")
 */
class StockReservation extends AbstractApiEntity
{
    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableEntity;

    /** Reservation time in minutes */
    const DEFAULT_RESERVATION_TIME = 15;

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
     * @ORM\Column(name="external_cart_id", type="string")
     */
    protected $externalCartId;

    /**
     * @var \Ramsey\Uuid\Uuid
     *
     * @ORM\Column(name="user_id", type="uuid")
     */
    protected $userId;

    /**
     * @var ProductVariant
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\ProductVariant", inversedBy="stockReservations")
     * @ORM\JoinColumn(name="variant_id", referencedColumnName="id", nullable=false)
     */
    protected $variant;

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
     * @var StockHistory
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\StockHistory", inversedBy="stockReservations")
     * @ORM\JoinColumn(name="origin_stock_history_id", referencedColumnName="id", nullable=false)
     */
    protected $originStockHistory;

    public function __construct()
    {
        $this->createdAt      = new \DateTime();
        $this->updatedAt      = new \DateTime();
        $this->expirationDate = new \DateTime('+' . self::DEFAULT_RESERVATION_TIME . ' min');
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
            'externalCartId'  => $this->getExternalCartId(),
            'title'           => $this->getTitle(),
            'changes'         => $this->getChanges(),
            'price'           => $this->getPrice(),
            'variantId'       => $this->getVariant()->getId()->__toString(),
            'productId'       => $this->getVariant()->getProduct()->getId()->__toString(),
            'warehouseId'     => $this->getOriginStockHistory()->getWarehouse()->getId()->__toString(),
            'warehouseZoneId' => $this->getOriginStockHistory()->getWarehouseZone()->getId()->__toString(),
            'unitId'          => $this->getUnit()->getId()->__toString(),
            'unitDescriptor'  => $this->getUnit()->getDescriptor(),
            'createdAt'       => $this->getCreatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'updatedAt'       => $this->getUpdatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'expirationDate'  => $this->getExpirationDate()->format(self::DEFAULT_DATE_FORMAT),
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

        return $this->getVariant()->getDescriptor() . ' ' . ($this->getChanges() > 0 ? '+' : '') . $this->getChanges() . ' ' . $this->getUnit()->getShortForm();
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
        $metadata->addPropertyConstraint('externalCartId', new Assert\NotNull());
        $metadata->addPropertyConstraint('externalCartId', new Assert\NotBlank());
        $metadata->addPropertyConstraint('variant', new Assert\NotNull());
        $metadata->addPropertyConstraint('userId', new Assert\NotNull());
        $metadata->addPropertyConstraint('unit', new Assert\NotNull());
        $metadata->addPropertyConstraint('expirationDate', new Assert\DateTime());

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
     * @return StockReservation
     */
    public function setChanges($changes)
    {
        $this->changes = (float) $changes;

        return $this;
    }

    /**
     * Get changes
     *
     * @return float
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
     * @return StockReservation
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
     * Set variant
     *
     * @param \App\Entity\ProductVariant $variant
     *
     * @return StockReservation
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
     * Set unit
     *
     * @param \App\Entity\Unit $unit
     *
     * @return StockReservation
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
     * @return StockReservation
     */
    public function setUserId(Uuid $userId): StockReservation
    {
        $this->userId = $userId;

        return $this;
    }


    /**
     * Set expirationDate
     *
     * @param \DateTime $expirationDate
     *
     * @return StockReservation
     */
    public function setExpirationDate($expirationDate = null)
    {
        if ($expirationDate instanceof \DateTime) {
            $this->expirationDate = clone $expirationDate;
        } else {
            $this->expirationDate = new \DateTime('+' . self::DEFAULT_RESERVATION_TIME . ' min');
        }

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
     * Set externalCartId
     *
     * @param string $externalCartId
     *
     * @return StockReservation
     */
    public function setExternalCartId(string $externalCartId)
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
     * Set originStockHistory
     *
     * @param \App\Entity\StockHistory $originStockHistory
     *
     * @return StockReservation
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
}
