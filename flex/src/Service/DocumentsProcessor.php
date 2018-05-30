<?php

namespace App\Service;

use App\Entity\ProductVariant;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DocumentsProcessor
 * @package App\Service
 */
class DocumentsProcessor
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

    public function refreshEntities()
    {
        $this->refreshAllVariants();
    }

    public function refreshAllVariants()
    {
        $variantRepo = $this->entityManager->getRepository(ProductVariant::class);
        $qb = $variantRepo->createQueryBuilder('o');
        $qb->select('o.id');
        $variantIds = $qb->getQuery()->getResult();
        $i = 1;
        /** @var Uuid $variantId */
        foreach ($variantIds as $variantId) {
            $variant = $variantRepo->find($variantId['id']->__toString());

            $variant->refreshDocument();

            if ($i % 100 === 0) {
                // todo clear only full products updated
//                $this->entityManager->flush();
//                $this->entityManager->clear($variant);
            }
            $i++;
        }

        $this->entityManager->flush();
    }
}
