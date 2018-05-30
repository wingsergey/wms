<?php

namespace App\Entity;

use App\Traits\ValueTypeTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductAttributeValueRepository")
 * @ORM\Table(name="product_attribute_values", uniqueConstraints={@ORM\UniqueConstraint(name="idx_product_attribute_value_external_unique", columns={"external_id", "user_id"}, options={"where": "external_id IS NOT NULL"})})
 */
class ProductAttributeValue extends AbstractApiEntity
{
    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableEntity;
    use ValueTypeTrait;

    const TYPE_TIMESTAMP = 'timestamp';
    const TYPE_STRING    = 'string';
    const TYPE_BOOLEAN   = 'boolean';

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
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Product", inversedBy="attributeValues", cascade={"remove"})
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $product;

    /**
     * @var ProductAttribute
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\ProductAttribute", inversedBy="values", cascade={"persist"})
     * @ORM\JoinColumn(name="attribute_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $attribute;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     */
    protected $type = self::TYPE_STRING;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     */
    protected $value;

    /** @var array of translated types */
    private static $typesTranslated = [
        self::TYPE_TIMESTAMP => 'Timestamp',
        self::TYPE_STRING    => 'String',
        self::TYPE_BOOLEAN   => 'Boolean',
    ];

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return array
     */
    public static function getAvailableTypes()
    {
        return array_keys(self::$typesTranslated);
    }

    /**
     * @return array
     */
    public static function getTranslatedTypes()
    {
        return self::$typesTranslated;
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
            'type'        => $this->getType(),
            'value'       => $this->getValue(),
            'title'       => $this->getTitle(),
            'productId'   => $this->getProduct()->getId()->__toString(),
            'attributeId' => $this->getAttribute()->getId()->__toString(),
            'createdAt'   => $this->getCreatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'updatedAt'   => $this->getUpdatedAt()->format(self::DEFAULT_DATE_FORMAT),
        ];

        return $fields;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->__toString();
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        if (!$this->getAttribute()) {
            return 'n/a';
        }

        return $this->getAttribute()->getName() . ': ' . $this->getValue();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('value', new Assert\NotBlank());
        $metadata->addPropertyConstraint('userId', new Assert\NotNull());
        $metadata->addPropertyConstraint('product', new Assert\NotNull());
        $metadata->addPropertyConstraint('attribute', new Assert\NotNull());
        $metadata->addPropertyConstraint('type', new Assert\Choice(\App\Entity\ProductAttributeValue::getAvailableTypes()));

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
     * Set type
     *
     * @param string $type
     *
     * @return ProductAttributeValue
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return ProductAttributeValue
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Set product
     *
     * @param \App\Entity\Product $product
     *
     * @return ProductAttributeValue
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
     * Set attribute
     *
     * @param \App\Entity\ProductAttribute $attribute
     *
     * @return ProductAttributeValue
     */
    public function setAttribute(\App\Entity\ProductAttribute $attribute)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * Get attribute
     *
     * @return \App\Entity\ProductAttribute
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * Set externalId
     *
     * @param string $externalId
     *
     * @return ProductAttributeValue
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
     * @return \Ramsey\Uuid\Uuid
     */
    public function getUserId(): \Ramsey\Uuid\Uuid
    {
        return $this->userId;
    }

    /**
     * @param \Ramsey\Uuid\Uuid $userId
     * @return ProductAttributeValue
     */
    public function setUserId(\Ramsey\Uuid\Uuid $userId): ProductAttributeValue
    {
        $this->userId = $userId;

        return $this;
    }

}
