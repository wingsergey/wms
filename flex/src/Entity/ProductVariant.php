<?php

namespace App\Entity;

use App\Traits\CodeGenerator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductVariantRepository")
 * @ORM\Table(name="product_variants", uniqueConstraints={@ORM\UniqueConstraint(name="idx_product_variant_external_unique", columns={"external_id", "user_id"}, options={"where": "external_id IS NOT NULL"})})
 */
class ProductVariant extends AbstractApiEntity
{
    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableEntity;
    use CodeGenerator;

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
     * @return \Ramsey\Uuid\Uuid
     */
    public function getUserId(): \Ramsey\Uuid\Uuid
    {
        return $this->userId;
    }

    /**
     * @param \Ramsey\Uuid\Uuid $userId
     * @return ProductVariant
     */
    public function setUserId(\Ramsey\Uuid\Uuid $userId): ProductVariant
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @var string
     *
     * @ORM\Column(type="string", options={"default" : ""})
     */
    protected $name = '';

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true, options={"default" : ""})
     */
    protected $sku = '';

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    protected $price = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="master", type="boolean")
     */
    protected $master = false;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Product", inversedBy="variants", cascade={"persist"})
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $product;

    /**
     * @var ArrayCollection|ProductOptionValue[]
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\ProductOptionValue", fetch="EXTRA_LAZY", orphanRemoval=true, cascade={"persist", "remove"})
     * @ORM\JoinTable(name="product_variant_option_value",
     *     joinColumns={@ORM\JoinColumn(name="variant_id", referencedColumnName="id", unique=false, nullable=false, onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="option_value_id", referencedColumnName="id", unique=true, nullable=false, onDelete="CASCADE")}
     *     )
     */
    protected $optionValues;

    /**
     * @var ArrayCollection|StockHistory[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\StockHistory", mappedBy="variant", fetch="EXTRA_LAZY", orphanRemoval=true, cascade={"persist", "remove"})
     */
    protected $stockHistories;

    /**
     * @var ArrayCollection|StockReservation[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\StockReservation", mappedBy="variant", fetch="EXTRA_LAZY", orphanRemoval=true, cascade={"persist", "remove"})
     */
    protected $stockReservations;

    /**
     * @var ArrayCollection|StockRevision[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\StockRevision", mappedBy="variant", fetch="EXTRA_LAZY", orphanRemoval=true, cascade={"persist", "remove"})
     */
    protected $stockRevisions;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default" : 0})
     */
    protected $onStock = 0;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default" : 0})
     */
    protected $reserved = 0;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $width;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $height;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $depth;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $weight;

    /**
     * Can contain anything (array, objects, nested objects...).
     *
     * @ORM\Column(type="json_document", options={"jsonb": true}, nullable=true)
     */
    public $document;

    public function __construct()
    {
        $this->setCode($this->generateCode());

        $this->optionValues      = new ArrayCollection();
        $this->stockHistories    = new ArrayCollection();
        $this->stockReservations = new ArrayCollection();
        $this->stockRevisions    = new ArrayCollection();

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

        return $this->getDocument();
    }

    /**
     * @param $lazy
     * @return array
     */
    private function generateApiFields(bool $lazy = false) : array
    {
        $fields = [
            'id'                   => $this->getId()->__toString(),
            'externalId'           => $this->getExternalId(),
            'productId'            => $this->getProduct()->getId()->__toString(),
            'defaultUnitId'        => $this->getProduct()->getDefaultUnit()->getId()->__toString(),
            'defaultUnitShortName' => $this->getProduct()->getDefaultUnit()->getShortForm(),
            'code'                 => $this->getCode(),
            'sku'                  => $this->getSku(),
            'price'                => $this->getPrice(),
            'onStock'              => $this->getOnStock(),
            'reserved'             => $this->getReserved(),
            'name'                 => $this->getName(),
            'descriptor'           => $this->getDescriptor(),
            'isMaster'             => $this->getMaster(),
            'width'                => $this->getWidth(),
            'height'               => $this->getHeight(),
            'depth'                => $this->getDepth(),
            'weight'               => $this->getWeight(),
            'createdAt'            => $this->getCreatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'updatedAt'            => $this->getUpdatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'optionValues'         => [],
            'units'                => [],
        ];

        /** @var ProductOptionValue $value */
        foreach ($this->getOptionValues() as $value) {
            $fields['optionValues'][] = $value->getApiFields($lazy);
        }

        /** @var Unit $unit */
        foreach ($this->getProduct()->getUnits() as $unit) {
            $fields['units'][] = $unit->getApiFields($lazy);
        }

        return $fields;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        if (!($this->getProduct() instanceof Product)) {
            return 'n/a';
        }

        $string = (string) $this->getProduct()->getName();

        if (!$this->getOptionValues()->isEmpty()) {
            $string .= '(';

            foreach ($this->getOptionValues() as $option) {
                $string .= $option->getOption()->getName() . ': ' . $option->getValue() . ', ';
            }

            $string = substr($string, 0, -2) . ')';
        }

        return $string;
    }

    /**
     * @return $this
     */
    public function refreshDocument()
    {
        $this->setDocument($this->generateApiFields(false));

        $this->getProduct()->refreshDocument();

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription() : string
    {
        /** @var Product $product */
        $product = $this->getProduct();

        $productName = $product->getName();

        $options = [];
        // add options
        foreach ($this->getOptionValues() as $option) {
            $options[] = $option->getValue();
        }

        $optionDescription = '';
        if ($options) {
            if ($optionDescription == '') {
                $optionDescription .= ': ';
            }
            $optionDescription .= ' ' . join(' ', $options);
        }

        return $productName . $optionDescription;
    }

    /**
     * @return bool
     */
    public function isMaster() : bool
    {
        return $this->master;
    }

    /**
     * @return string
     */
    public function getDescriptor() : string
    {
        $name = empty($this->getName()) ? $this->getProduct()->getName() : $this->getName();

        return trim(sprintf('%s%s (%s)', $name, ($this->getMaster() ? '*' : ''), $this->code));
    }

    /**
     * @param ProductOptionValue $optionValue
     * @return bool
     */
    public function hasOptionValue(ProductOptionValue $optionValue) : bool
    {
        return $this->optionValues->contains($optionValue);
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
//        $metadata->addPropertyConstraint('name', new Assert\NotBlank());
        $metadata->addPropertyConstraint('userId', new Assert\NotNull());
        $metadata->addPropertyConstraint('product', new Assert\NotNull());
        $metadata->addPropertyConstraint('code', new Assert\NotBlank());
//        $metadata->addPropertyConstraint('sku', new Assert\Optional(new Assert\NotBlank()));

        $metadata->addPropertyConstraint('price', new Assert\NotBlank());
        $metadata->addPropertyConstraint('price', new Assert\Type([
            'type'    => 'integer',
            'message' => 'The price {{ value }} is not a valid {{ type }}.',
        ]));

        $metadata->addPropertyConstraint('master', new Assert\Type([
            'type'    => 'bool',
            'message' => 'The master flag {{ value }} is not a valid {{ type }} value.',
        ]));

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
     * Set code
     *
     * @param string $code
     *
     * @return ProductVariant
     */
    public function setCode($code)
    {
        $this->code = $code;

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
     * Set product
     *
     * @param \App\Entity\Product $product
     *
     * @return ProductVariant
     */
    public function setProduct(\App\Entity\Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \App\Entity\Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ProductVariant
     */
    public function setName($name)
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add optionValue
     *
     * @param \App\Entity\ProductOptionValue $optionValue
     *
     * @return ProductVariant
     */
    public function addOptionValue(\App\Entity\ProductOptionValue $optionValue)
    {
        if (!$this->hasOptionValue($optionValue)) {
            $this->optionValues[] = $optionValue;
        }

        return $this;
    }

    /**
     * Remove optionValue
     *
     * @param \App\Entity\ProductOptionValue $optionValue
     */
    public function removeOptionValue(\App\Entity\ProductOptionValue $optionValue)
    {
        $this->optionValues->removeElement($optionValue);
    }

    /**
     * Get optionValues
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOptionValues()
    {
        return $this->optionValues;
    }

    /**
     * Add stockHistory
     *
     * @param \App\Entity\StockHistory $stockHistory
     *
     * @return ProductVariant
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
     * Set master
     *
     * @param boolean $master
     *
     * @return ProductVariant
     */
    public function setMaster($master)
    {
        $this->master = (bool) $master;

        return $this;
    }

    /**
     * Get master
     *
     * @return boolean
     */
    public function getMaster()
    {
        return $this->master;
    }

    /**
     * Set sku
     *
     * @param string $sku
     *
     * @return ProductVariant
     */
    public function setSku($sku)
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * Get sku
     *
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * Set price
     *
     * @param integer $price
     *
     * @return ProductVariant
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
     * Set externalId
     *
     * @param string $externalId
     *
     * @return ProductVariant
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
     * Add stockReservation
     *
     * @param \App\Entity\StockReservation $stockReservation
     *
     * @return ProductVariant
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
        return $this->stockReservations;
    }

    /**
     * Set onStock
     *
     * @param float $onStock
     *
     * @return ProductVariant
     */
    public function setOnStock($onStock)
    {
        $this->onStock = $onStock;

        return $this;
    }

    /**
     * Get onStock
     *
     * @return float
     */
    public function getOnStock()
    {
        return $this->onStock;
    }

    /**
     * Set reserved
     *
     * @param float $reserved
     *
     * @return ProductVariant
     */
    public function setReserved($reserved)
    {
        $this->reserved = $reserved;

        return $this;
    }

    /**
     * Get reserved
     *
     * @return float
     */
    public function getReserved()
    {
        return $this->reserved;
    }

    /**
     * Set document
     *
     * @param array $document
     *
     * @return ProductVariant
     */
    public function setDocument($document)
    {
        $this->document = $document;

        return $this;
    }

    /**
     * Get document
     *
     * @return array
     */
    public function getDocument()
    {
        if (!$this->document) {
            return $this->generateApiFields(false);
        }

        return $this->document;
    }

    /**
     * Set width
     *
     * @param float $width
     *
     * @return ProductVariant
     */
    public function setWidth($width)
    {
        $this->width = (float) $width;

        return $this;
    }

    /**
     * Get width
     *
     * @return float
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param float $height
     *
     * @return ProductVariant
     */
    public function setHeight($height)
    {
        $this->height = (float) $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set depth
     *
     * @param float $depth
     *
     * @return ProductVariant
     */
    public function setDepth($depth)
    {
        $this->depth = (float) $depth;

        return $this;
    }

    /**
     * Get depth
     *
     * @return float
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Set weight
     *
     * @param float $weight
     *
     * @return ProductVariant
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Add stockRevision.
     *
     * @param \App\Entity\StockRevision $stockRevision
     *
     * @return ProductVariant
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
