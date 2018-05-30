<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UnitRepository")
 * @ORM\Table(name="units", uniqueConstraints={@ORM\UniqueConstraint(name="idx_unit_external_unique", columns={"external_id", "user_id"}, options={"where": "external_id IS NOT NULL"})})
 */
class Unit extends AbstractApiEntity
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
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true, options={"default" : ""})
     */
    protected $shortForm = '';

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true, options={"default" : ""})
     */
    protected $description = '';

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Product", inversedBy="units")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=true)
     */
    private $product;

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
            'id'          => $this->getId()->__toString(),
            'externalId'  => $this->getExternalId(),
            'title'       => $this->getTitle(),
            'shortForm'   => $this->getShortForm(),
            'description' => $this->getDescription(),
            'createdAt'   => $this->getCreatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'updatedAt'   => $this->getUpdatedAt()->format(self::DEFAULT_DATE_FORMAT),
            'descriptor'  => $this->getDescriptor(),
        ];

        return $fields;
    }

    /**
     * @return string
     */
    public function getDescriptor()
    {
        $descriptor = $this->getTitle();
        if ($this->getShortForm()) {
            $descriptor = $this->getShortForm();
        }

        if ($this->getProduct()) {
            $descriptor .= ' (' . $this->getProduct()->getName() . ')';
        }

        return $descriptor;
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('title', new Assert\NotBlank());
//        $metadata->addPropertyConstraint('shortForm', new Assert\Optional(new Assert\NotBlank()));
//        $metadata->addPropertyConstraint('description', new Assert\Optional(new Assert\NotBlank()));
        $metadata->addPropertyConstraint('userId', new Assert\NotNull());
//        $metadata->addPropertyConstraint('product', new Assert\Optional(new Assert\NotNull()));

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
     * Set title
     *
     * @param string $title
     *
     * @return Unit
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
     * @return Unit
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
     * Set shortForm
     *
     * @param string $shortForm
     *
     * @return Unit
     */
    public function setShortForm($shortForm)
    {
        $this->shortForm = $shortForm;

        return $this;
    }

    /**
     * Get shortForm
     *
     * @return string
     */
    public function getShortForm()
    {
        return $this->shortForm;
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
     * @return Unit
     */
    public function setUserId(\Ramsey\Uuid\Uuid $userId): Unit
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Set product
     *
     * @param \App\Entity\Product $product
     *
     * @return Unit
     */
    public function setProduct(\App\Entity\Product $product = null)
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
     * Set externalId
     *
     * @param string $externalId
     *
     * @return Unit
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
