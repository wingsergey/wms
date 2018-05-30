<?php

namespace App\Entity;

use App\Traits\ValueTypeTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductOptionValueRepository")
 * @ORM\Table(name="product_option_values", uniqueConstraints={@ORM\UniqueConstraint(name="idx_product_option_value_external_unique", columns={"external_id", "user_id"}, options={"where": "external_id IS NOT NULL"})})
 */
class ProductOptionValue extends AbstractApiEntity
{
    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableEntity;
    use ValueTypeTrait;

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
     * @ORM\Column(type="string", nullable=false)
     */
    protected $type = ProductAttributeValue::TYPE_STRING;

    /**
     * @var ProductOption
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\ProductOption", inversedBy="values", cascade={"persist"})
     * @ORM\JoinColumn(name="option_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $option;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     */
    protected $value;

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
            'id'         => $this->getId()->__toString(),
            'externalId' => $this->getExternalId(),
            'type'       => $this->getType(),
            'value'      => $this->getValue(),
            'rawValue'   => $this->getRawValue(),
            'title'      => $this->getTitle(),
            'optionId'   => $this->getOption()->getId()->__toString(),
            'createdAt'  => $this->getCreatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'updatedAt'  => $this->getUpdatedAt()->format(self::DEFAULT_DATE_FORMAT),
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
        if (!$this->getOption()) {
            return 'n/a';
        }

        return $this->getOption()->getName() . ': ' . $this->getRawValue();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('value', new Assert\NotBlank());
        $metadata->addPropertyConstraint('userId', new Assert\NotNull());
        $metadata->addPropertyConstraint('option', new Assert\NotNull());
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
     * @return ProductOptionValue
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
     * @return ProductOptionValue
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Set option
     *
     * @param \App\Entity\ProductOption $option
     *
     * @return ProductOptionValue
     */
    public function setOption(\App\Entity\ProductOption $option)
    {
        $this->option = $option;

        return $this;
    }

    /**
     * Get option
     *
     * @return \App\Entity\ProductOption
     */
    public function getOption()
    {
        return $this->option;
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
     * @return ProductOptionValue
     */
    public function setUserId(\Ramsey\Uuid\Uuid $userId): ProductOptionValue
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Set externalId
     *
     * @param string $externalId
     *
     * @return ProductOptionValue
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
