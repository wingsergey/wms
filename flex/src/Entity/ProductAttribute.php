<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductAttributeRepository")
 * @ORM\Table(name="product_attributes", uniqueConstraints={@ORM\UniqueConstraint(name="idx_product_attribute_external_unique", columns={"external_id", "user_id"}, options={"where": "external_id IS NOT NULL"})})
 */
class ProductAttribute extends AbstractApiEntity
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
     * @ORM\Column(type="string", nullable=false)
     */
    protected $name;

    /**
     * @var ArrayCollection|ProductAttributeValue[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\ProductAttributeValue", mappedBy="attribute", orphanRemoval=true, cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     */
    protected $values;

    /**
     * Constructor
     */
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
            'values'     => [],
        ];

        /** @var ProductAttributeValue $value */
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
     * Set name
     *
     * @param string $name
     *
     * @return ProductAttribute
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
     * Add value
     *
     * @param \App\Entity\ProductAttributeValue $value
     *
     * @return ProductAttribute
     */
    public function addValue(\App\Entity\ProductAttributeValue $value)
    {
        $this->values[] = $value;

        return $this;
    }

    /**
     * Remove value
     *
     * @param \App\Entity\ProductAttributeValue $value
     */
    public function removeValue(\App\Entity\ProductAttributeValue $value)
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
     * @return \Ramsey\Uuid\Uuid
     */
    public function getUserId(): \Ramsey\Uuid\Uuid
    {
        return $this->userId;
    }

    /**
     * @param \Ramsey\Uuid\Uuid $userId
     * @return ProductAttribute
     */
    public function setUserId(\Ramsey\Uuid\Uuid $userId): ProductAttribute
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Set externalId
     *
     * @param string $externalId
     *
     * @return ProductAttribute
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
