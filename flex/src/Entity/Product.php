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
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 * @ORM\Table(name="products", uniqueConstraints={@ORM\UniqueConstraint(name="idx_product_external_unique", columns={"external_id", "user_id"}, options={"where": "external_id IS NOT NULL"})})
 *
 * @ORM\HasLifecycleCallbacks()
 */
class Product extends AbstractApiEntity
{
    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableEntity;
    use CodeGenerator;

    /*
     * Variant selection methods.
     *
     * 1) Choice - A list of all variants is displayed to user.
     *
     * 2) Match  - Each product option is displayed as select field.
     *             User selects the values and we match them to variant.
     */
    const VARIANT_SELECTION_CHOICE = 'choice';
    const VARIANT_SELECTION_MATCH  = 'match';

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
     * @var string|null
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
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true, options={"default" : ""})
     */
    protected $description = '';

    /**
     * @var string
     *
     * @ORM\Column(name="variant_selection_method", type="string", nullable=false)
     */
    protected $variantSelectionMethod = self::VARIANT_SELECTION_CHOICE;

    /**
     * @var ArrayCollection|ProductAttributeValue[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\ProductAttributeValue", mappedBy="product", orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    protected $attributeValues;

    /**
     * @var ArrayCollection|ProductVariant[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\ProductVariant", mappedBy="product", orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    protected $variants;

    /**
     * @var ArrayCollection|ProductOption[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\ProductOption", mappedBy="product", orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    protected $options;

    /**
     * @var ArrayCollection|Unit[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Unit", mappedBy="product", orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    protected $units;

    /**
     * @var Unit
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Unit", cascade={"persist"})
     * @ORM\JoinColumn(name="default_unit_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $defaultUnit;

    /**
     * Can contain anything (array, objects, nested objects...).
     *
     * @ORM\Column(type="json_document", options={"jsonb": true}, nullable=true)
     */
    protected $document;

    public function __construct()
    {
        $this->attributeValues = new ArrayCollection();
        $this->variants        = new ArrayCollection();
        $this->options         = new ArrayCollection();
        $this->units           = new ArrayCollection();
        $this->createdAt       = new \DateTime();
        $this->updatedAt       = new \DateTime();
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
            'id'                     => $this->getId()->__toString(),
            'externalId'             => $this->getExternalId(),
            'name'                   => $this->getName(),
            'description'            => $this->getDescription(),
            'code'                   => $this->getCode(),
            'defaultUnit'            => $this->getDefaultUnit() ? $this->getDefaultUnit()->getDescriptor() : '',
            'defaultUnitId'          => $this->getDefaultUnit() ? $this->getDefaultUnit()->getId()->__toString() : '',
            'variantSelectionMethod' => $this->getVariantSelectionMethod(),
            'createdAt'              => $this->getCreatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'updatedAt'              => $this->getUpdatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'attributesValues'       => [],
            'variants'               => [],
            'options'                => [],
            'units'                  => [],
        ];

        /** @var ProductVariant $variant */
        foreach ($this->getVariants() as $variant) {
            $fields['variants'][] = $variant->getDocument();
        }

        /** @var ProductOption $option */
        foreach ($this->getOptions() as $option) {
            $fields['options'][] = $option->getApiFields($lazy);
        }

        /** @var ProductAttributeValue $attributeValue */
        foreach ($this->getAttributeValues() as $attributeValue) {
            $fields['attributesValues'][] = $attributeValue->getApiFields($lazy);
        }

        /** @var Unit $unit */
        foreach ($this->getUnits() as $unit) {
            $fields['units'][] = $unit->getApiFields($lazy);
        }

        return $fields;
    }

    /**
     * @return $this
     */
    public function refreshDocument()
    {
        $this->setDocument($this->generateApiFields(false));

        return $this;
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
     * @return Product
     */
    public function setUserId(\Ramsey\Uuid\Uuid $userId): Product
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @ORM\PrePersist()
     */
    public function createMasterVariant()
    {
        if (null === $this->getMasterVariant()) {
            $variant = new ProductVariant();
            $variant->setUserId($this->getUserId());

            $this->setMasterVariant($variant);
        }
    }

    /**
     * @return ProductVariant|null
     */
    public function getMasterVariant()
    {
        /** @var ProductVariant $variant */
        foreach ($this->variants as $variant) {
            if ($variant->isMaster()) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * @param ProductVariant $masterVariant
     */
    public function setMasterVariant(ProductVariant $masterVariant)
    {
        $masterVariant->setMaster(true);

        if (!$this->variants->contains($masterVariant)) {
            $masterVariant->setProduct($this);
            $this->variants->add($masterVariant);
        }
    }

    /**
     * @return bool
     */
    public function isSimple() : bool
    {
        return 1 === $this->variants->count() && !$this->hasOptions();
    }

    /**
     * @return bool
     */
    public function hasVariants() : bool
    {
        return !$this->getVariants()->isEmpty();
    }

    /**
     * @param ProductVariant $variant
     * @return bool
     */
    public function hasVariant(ProductVariant $variant) : bool
    {
        return $this->variants->contains($variant);
    }

    /**
     * @return bool
     */
    public function hasOptions() : bool
    {
        return !$this->options->isEmpty();
    }

    /**
     * @param ProductAttributeValue $attributeValue
     * @return bool
     */
    public function hasAttributeValue(ProductAttributeValue $attributeValue) : bool
    {
        return $this->attributeValues->contains($attributeValue);
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Product
     */
    public function setCode($code)
    {
        $this->getMasterVariant()->setCode($code);

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode() : string
    {
        return $this->getMasterVariant()->getCode();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank());
        $metadata->addPropertyConstraint('description', new Assert\Optional());
        $metadata->addPropertyConstraint('variantSelectionMethod', new Assert\Choice([
            \App\Entity\Product::VARIANT_SELECTION_CHOICE,
            \App\Entity\Product::VARIANT_SELECTION_MATCH,
        ]));
        $metadata->addPropertyConstraint('userId', new Assert\NotNull());
        $metadata->addPropertyConstraint('defaultUnit', new Assert\NotNull());

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
     * Set variantSelectionMethod
     *
     * @param string $variantSelectionMethod
     *
     * @return Product
     */
    public function setVariantSelectionMethod($variantSelectionMethod)
    {
        $this->variantSelectionMethod = $variantSelectionMethod;

        return $this;
    }

    /**
     * Get variantSelectionMethod
     *
     * @return string
     */
    public function getVariantSelectionMethod()
    {
        return $this->variantSelectionMethod;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Product
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
     * Add attribute value
     *
     * @param \App\Entity\ProductAttributeValue $attributeValue
     *
     * @return Product
     */
    public function addAttributeValue(\App\Entity\ProductAttributeValue $attributeValue)
    {
        if (!$this->hasAttributeValue($attributeValue)) {
            $this->attributeValues->add($attributeValue);
        }

        return $this;
    }

    /**
     * Remove attribute value
     *
     * @param ProductAttributeValue $attributeValue
     */
    public function removeAttributeValue(\App\Entity\ProductAttributeValue $attributeValue)
    {
        $this->attributeValues->removeElement($attributeValue);
    }

    /**
     * Get attribute values
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttributeValues()
    {
        return $this->attributeValues;
    }

    /**
     * Add variant
     *
     * @param \App\Entity\ProductVariant $variant
     *
     * @return Product
     */
    public function addVariant(\App\Entity\ProductVariant $variant)
    {
        $this->variants[] = $variant;

        return $this;
    }

    /**
     * Remove variant
     *
     * @param \App\Entity\ProductVariant $variant
     */
    public function removeVariant(\App\Entity\ProductVariant $variant)
    {
        $this->variants->removeElement($variant);
    }

    /**
     * Get variants
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVariants()
    {
        return $this->variants;
    }

    /**
     * Add option
     *
     * @param \App\Entity\ProductOption $option
     *
     * @return Product
     */
    public function addOption(\App\Entity\ProductOption $option)
    {
        $this->options[] = $option;

        return $this;
    }

    /**
     * Remove option
     *
     * @param \App\Entity\ProductOption $option
     */
    public function removeOption(\App\Entity\ProductOption $option)
    {
        $this->options->removeElement($option);
    }

    /**
     * Get options
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Product
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * Add unit
     *
     * @param \App\Entity\Unit $unit
     *
     * @return Product
     */
    public function addUnit(\App\Entity\Unit $unit)
    {
        $this->units[] = $unit;

        return $this;
    }

    /**
     * Remove unit
     *
     * @param \App\Entity\Unit $unit
     */
    public function removeUnit(\App\Entity\Unit $unit)
    {
        $this->units->removeElement($unit);
    }

    /**
     * Get units
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUnits()
    {
        return $this->units;
    }

    /**
     * Set externalId
     *
     * @param string $externalId
     *
     * @return Product
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
     * Set defaultUnit
     *
     * @param \App\Entity\Unit $defaultUnit
     *
     * @return Product
     */
    public function setDefaultUnit(\App\Entity\Unit $defaultUnit = null)
    {
        $this->defaultUnit = $defaultUnit;

        return $this;
    }

    /**
     * Get defaultUnit
     *
     * @return \App\Entity\Unit
     */
    public function getDefaultUnit()
    {
        return $this->defaultUnit;
    }

    /**
     * Set document
     *
     * @param array $document
     *
     * @return Product
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
}
