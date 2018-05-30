<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductOptionRepository")
 * @ORM\Table(name="product_options", uniqueConstraints={@ORM\UniqueConstraint(name="idx_product_option_external_unique", columns={"external_id", "user_id"}, options={"where": "external_id IS NOT NULL"})})
 */
class ProductOption extends AbstractApiEntity
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
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Product", inversedBy="options", cascade={"persist"})
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $product;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     */
    protected $name;

    /**
     * @var ArrayCollection|ProductOptionValue[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\ProductOptionValue", mappedBy="option", orphanRemoval=true, cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     */
    protected $values;

    public function __construct()
    {
        $this->values = new ArrayCollection();

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
            'name'       => $this->getName(),
            'createdAt'  => $this->getCreatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'updatedAt'  => $this->getUpdatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'productId'  => $this->getProduct()->getId()->__toString(),
            'values'     => [],
        ];

        /** @var ProductOptionValue $value */
        foreach ($this->getValues() as $value) {
            $fields['values'][] = $value->getApiFields($lazy);
        }

        return $fields;
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank());
        $metadata->addPropertyConstraint('userId', new Assert\NotNull());
        $metadata->addPropertyConstraint('product', new Assert\NotNull());

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
     * Set product
     *
     * @param \App\Entity\Product $product
     *
     * @return ProductOption
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
     * Add value
     *
     * @param \App\Entity\ProductOptionValue $value
     *
     * @return ProductOption
     */
    public function addValue(\App\Entity\ProductOptionValue $value)
    {
        $this->values[] = $value;

        return $this;
    }

    /**
     * Remove value
     *
     * @param \App\Entity\ProductOptionValue $value
     */
    public function removeValue(\App\Entity\ProductOptionValue $value)
    {
        $this->values->removeElement($value);
    }

    /**
     * Get values
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ProductOption
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
     * @return \Ramsey\Uuid\Uuid
     */
    public function getUserId(): \Ramsey\Uuid\Uuid
    {
        return $this->userId;
    }

    /**
     * @param \Ramsey\Uuid\Uuid $userId
     * @return ProductOption
     */
    public function setUserId(\Ramsey\Uuid\Uuid $userId): ProductOption
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Set externalId
     *
     * @param string $externalId
     *
     * @return ProductOption
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
