<?php

namespace App\Service;

use App\Entity\StockRevision;
use App\Entity\WarehouseZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RevisionProcessor
{
    /** @var CommonProcessor */
    protected $commonProcessor;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * StockProcessor constructor.
     * @param CommonProcessor $commonProcessor
     * @param EntityManagerInterface $entityManager
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        CommonProcessor $commonProcessor,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher
    ) {
        $this->commonProcessor = $commonProcessor;
        $this->entityManager   = $entityManager;

        $this->dispatcher = $dispatcher;
    }
}
