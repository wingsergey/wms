<?php

namespace App\Service;

use App\Entity\AbstractApiEntity;
use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Entity\StockHistory;
use App\Entity\StockReservation;
use App\Entity\StockRevision;
use App\Entity\Unit;
use App\Entity\UnitEqual;
use App\Entity\Warehouse;
use App\Entity\WarehouseZone;
use App\EventListener\LogsAuditCreateEvent;
use App\EventListener\VariantBalanceChangedEvent;
use App\EventListener\VariantReserveChangedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StockProcessor
{
    /** @var CommonProcessor */
    protected $commonProcessor;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var array */
    protected static $stockTable = [];
    protected static $notEnoughStockTable = [];

    private static $unitEqualsHashTable;
    private static $unitEqualsMap;

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
    )
    {
        $this->commonProcessor = $commonProcessor;
        $this->entityManager = $entityManager;

        $this->dispatcher = $dispatcher;
    }

    /**
     * @param StockHistory $entity
     * @return StockHistory|bool
     */
    public function processStockChanges(StockHistory $entity)
    {
        if ($entity->getChanges() < 0) {
            return $this->subStock($entity);
        }
        else {
            return $this->addStock($entity);
        }
    }

    /**
     * @param StockReservation $entity
     * @return array
     * @throws \Exception
     */
    public function reserveVariantOnStock(StockReservation $entity)
    {
        // check if variant is on stock
        $stockHistories = $this->findEnoughStocks($entity->getVariant(), $entity->getChanges(), $entity->getUnit());
        if ($stockHistories === null) {
            throw new \Exception('There are no enough items on stock for reserve');
        }

        $stockReservations = [];
        $qtyCircle = $entity->getChanges();
        /** @var StockHistory $stockHistory */
        foreach ($stockHistories as $stockHistory) {
            if ($qtyCircle == 0) {
                break;
            }

            $stockHistoryBalance = $this->calculateStockHistoryBalance($stockHistory, $entity->getUnit());
            if ($stockHistoryBalance === 0) {
                continue;
            }

            // todo сначала списывать целые запаси если списывается не минимальная единица
            if ($stockHistoryBalance < $qtyCircle) {
                $diff = $stockHistoryBalance;
                $qtyCircle -= $diff;
            }
            else {
                $diff = $qtyCircle;
                $qtyCircle = 0;
            }

            $newStockReserve = new StockReservation();
            $newStockReserve->setUserId($this->commonProcessor->getUserUUID());
            $newStockReserve->setVariant($entity->getVariant());
            $newStockReserve->setUnit($entity->getUnit());
            $newStockReserve->setPrice($entity->getPrice());
            $newStockReserve->setExpirationDate($entity->getExpirationDate());
            $newStockReserve->setExternalCartId($entity->getExternalCartId());
            $newStockReserve->setOriginStockHistory($stockHistory);
            $newStockReserve->setChanges($diff);

            $this->entityManager->persist($newStockReserve);
            $this->entityManager->persist($entity->getVariant());

            $stockReservations[] = $newStockReserve;
        }

        $this->entityManager->flush();

        if (!$stockReservations) {
            throw new \Exception('No stocks reserved');
        }

        $this->dispatcher->dispatch(VariantReserveChangedEvent::NAME, new VariantReserveChangedEvent($entity->getVariant()));

        /** @var StockReservation $stockReservation */
        foreach ($stockReservations as $stockReservation) {
            $this->dispatcher->dispatch(LogsAuditCreateEvent::NAME, new LogsAuditCreateEvent([
                'message' => sprintf(
                    'Товар "%s" %s %s зарезервирован на складе по цене %s грн. до %s',
                    $stockReservation->getVariant()->getDescription(),
                    abs($stockReservation->getChanges()),
                    $stockReservation->getUnit()->getDescriptor(),
                    CommonProcessor::priceFilter($stockReservation->getPrice()),
                    $stockReservation->getExpirationDate()->format(AbstractApiEntity::DEFAULT_DATE_FORMAT)
                ),
                'filters' => [
                    $stockReservation->getClassName()     => $stockReservation->getId()->__toString(),
                    $entity->getVariant()->getClassName() => $entity->getVariant()->getId()->__toString(),
                ],
            ], LogsAuditCreateEvent::TYPE_STOCK_RESERVATION));
        }

        return $stockReservations;
    }

    /**
     * @param StockRevision $entity
     * @return StockRevision
     * @throws \Exception
     */
    public function addRevision(StockRevision $entity)
    {
        $defaultUnit = $entity->getVariant()->getProduct()->getDefaultUnit();
        $currentQty = $entity->getVariant()->getOnStock();

        $entity->setState(StockRevision::STATE_FINISHED);
        $entity->setRecalculatedAt(new \DateTime());
        $entity->setDefaultUnit($defaultUnit);
        $entity->setCurrentQty($currentQty);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    /**
     * @param StockRevision $entity
     * @return StockRevision
     * @throws \Exception
     */
    public function planRevision(StockRevision $entity)
    {
        $entity->setState(StockRevision::STATE_PLANNED);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    /**
     * @param StockReservation $entity
     * @return bool
     */
    public function deleteStockReservation(StockReservation $entity)
    {
        $stockReservationRepository = $this->entityManager->getRepository(StockReservation::class);
        if ($entity->getExternalCartId()) {
            $stockReservations = $stockReservationRepository->getExternalCartStockReserves($entity->getExternalCartId(), $entity->getVariant());
        }
        else {
            $stockReservations = [$entity];
        }

        $eventsData = [];
        /** @var StockReservation $stockReservation */
        foreach ($stockReservations as $stockReservation) {
            $this->entityManager->remove($stockReservation);

            $eventsData[] = [
                'message' => sprintf(
                    'Резерв товара "%s" %s %s по цене %s грн. до %s УДАЛЕН!',
                    $stockReservation->getVariant()->getDescription(),
                    abs($stockReservation->getChanges()),
                    $stockReservation->getUnit()->getDescriptor(),
                    CommonProcessor::priceFilter($stockReservation->getPrice()),
                    $stockReservation->getExpirationDate()->format(AbstractApiEntity::DEFAULT_DATE_FORMAT)
                ),
                'filters' => [
                    $stockReservation->getVariant()->getClassName() => $stockReservation->getVariant()->getId()->__toString(),
                    $stockReservation->getClassName()               => $stockReservation->getId()->__toString(),
                ],
            ];
        }

        $this->entityManager->flush();

        $this->dispatcher->dispatch(VariantReserveChangedEvent::NAME, new VariantReserveChangedEvent($entity->getVariant()));

        foreach ($eventsData as $eventsDatum) {
            $this->dispatcher->dispatch(LogsAuditCreateEvent::NAME, new LogsAuditCreateEvent($eventsDatum, LogsAuditCreateEvent::TYPE_STOCK_RESERVATION));
        }

        return true;
    }

    /**
     * @param StockRevision $entity
     * @return bool
     */
    public function deleteStockRevision(StockRevision $entity)
    {
        $logData = [
            'message' => sprintf(
                'Ревизия товара "%s" %s %s УДАЛЕНА!',
                $entity->getVariant()->getDescription(),
                abs($entity->getQty()),
                $entity->getUnit()->getDescriptor()
            ),
            'filters' => [
                $entity->getClassName() => $entity->getId()->__toString(),
            ],
        ];

        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(LogsAuditCreateEvent::NAME, new LogsAuditCreateEvent($logData, LogsAuditCreateEvent::TYPE_STOCK_REVISION));

        return true;
    }

    public function removeExpiredReserves()
    {
        $repo = $this->entityManager->getRepository(StockReservation::class);
        /** @var StockReservation $expiredStockReserve */
        foreach ($repo->getExpiredStockReserves() as $expiredStockReserve) {
            $this->deleteStockReservation($expiredStockReserve);
        }
    }

    /**
     * @param string $externalCartId
     */
    public function removeExternalCartStockReserves(string $externalCartId)
    {
        $qb = $this->entityManager->getRepository(StockReservation::class)->createQueryBuilder('o');

        $qb
            ->andWhere('o.externalCartId = :externalCartId')
            ->setParameter('externalCartId', $externalCartId);
        $reserves = $qb->getQuery()->getResult();

        $variants = [];
        /** @var StockReservation $reserve */
        foreach ($reserves as $reserve) {
            if (!isset($variants[$reserve->getId()->__toString()])) {
                $variants[$reserve->getId()->__toString()] = $reserve->getVariant();
            }
            $this->deleteStockReservation($reserve);
        }

        $this->entityManager->flush();

        /** @var ProductVariant $variant */
        foreach ($variants as $variant) {
            $this->dispatcher->dispatch(VariantReserveChangedEvent::NAME, new VariantReserveChangedEvent($variant));
        }
    }

    /**
     * @param ProductVariant $variant
     */
    public function refreshVariantReserveBalance(ProductVariant $variant)
    {
        $balance = $this->calculateVariantReserveBalance($variant, $variant->getProduct()->getDefaultUnit());
        $variant->setReserved($balance);

        $this->entityManager->persist($variant);
        $this->entityManager->flush();
    }

    /**
     * @param StockHistory $entity
     * @return array|bool|null
     */
    public function subStock(StockHistory $entity)
    {
        $newStockHistories = [];

        $qty = abs($entity->getChanges());

        // check if stock was already subset, for cases when order item qty was edited
        if ($entity->getExternalItemId() !== null) {
            $stockDeducted = $this->countItemStockDeducted($entity->getExternalItemId(), $entity->getUnit());
            if ($stockDeducted >= $qty) {
                // TODO do we need to return difference? Seems no, but ...
                return null;
            }

            $qty -= $stockDeducted;
        }

        // check if stock was already reserved
        // todo check if reserve is counted
        $stockReservationRepository = $this->entityManager->getRepository(StockReservation::class);

        $reservedBalance = 0;
        if ($entity->getExternalCartId()) {
            $stockReserves = $stockReservationRepository->getExternalCartStockReserves($entity->getExternalCartId(), $entity->getVariant());
            /** @var StockReservation $stockReserve */
            foreach ($stockReserves as $stockReserve) {
                // convert reserved qty to request units
                $multiplier = $this->getMultiplierFromHashTable($stockReserve->getUnit(), $entity->getUnit());
                $historyBalance = $stockReserve->getChanges() * $multiplier;

                $reservedBalance += $historyBalance;
            }
        }

        $qty -= $reservedBalance;

        // subset stocks not covered by reservation
        $stockHistories = $this->findEnoughStocks($entity->getVariant(), $qty, $entity->getUnit());
        if ($stockHistories === null) {
            return false;
        }

        // todo сначала списывать целые запаси если списывается не минимальная единица

        if ($qty > 0) {
            $qtyCircle = $qty;
            /** @var StockHistory $stockHistory */
            foreach ($stockHistories as $stockHistory) {
                $newStockHistory = new StockHistory();
                $newStockHistory->setUnit($entity->getUnit());
                $newStockHistory->setUserId($entity->getUserId());
                $newStockHistory->setVariant($entity->getVariant());
                if ($entity->getExternalItemId() !== null) {
                    $newStockHistory->setExternalItemId($entity->getExternalItemId());
                }

                $diff = $this->processSubStockChanges($newStockHistory, $stockHistory, $qtyCircle);

                $newStockHistories[] = $newStockHistory;

                if ($diff >= 0) {
                    break;
                }
            }
        }

        $this->entityManager->persist($entity->getVariant());

        // remove reserves for externalCartId
        if ($entity->getExternalCartId()) {
            $this->removeExternalCartStockReserves($entity->getExternalCartId());
        }

        $this->entityManager->flush();

        $this->dispatcher->dispatch(VariantBalanceChangedEvent::NAME, new VariantBalanceChangedEvent($entity->getVariant()));

        /** @var StockHistory $newStockHistory */
        foreach ($newStockHistories as $newStockHistory) {
            $this->dispatcher->dispatch(LogsAuditCreateEvent::NAME, new LogsAuditCreateEvent([
                'message' => sprintf(
                    'Товар "%s" %s %s списан со склада по цене %s грн.',
                    $entity->getVariant()->getDescription(),
                    abs($newStockHistory->getChanges()),
                    $entity->getUnit()->getDescriptor(),
                    CommonProcessor::priceFilter($newStockHistory->getPrice())
                ),
                'filters' => [
                    $newStockHistory->getClassName()      => $newStockHistory->getId()->__toString(),
                    $entity->getVariant()->getClassName() => $entity->getVariant()->getId()->__toString(),
                ],
            ], LogsAuditCreateEvent::TYPE_STOCK_BALANCE_CHANGE));
        }

        return $newStockHistories;
    }

    /**
     * @param StockHistory $entity
     * @return StockHistory
     */
    public function addStock(StockHistory $entity)
    {
        $this->entityManager->persist($entity);
        $this->entityManager->persist($entity->getVariant());
        $this->entityManager->flush();

        $this->dispatcher->dispatch(VariantBalanceChangedEvent::NAME, new VariantBalanceChangedEvent($entity->getVariant()));

        $this->dispatcher->dispatch(LogsAuditCreateEvent::NAME, new LogsAuditCreateEvent([
            'message' => sprintf(
                'Товар "%s" %s %s добавлен на склад по цене %s грн.',
                $entity->getVariant()->getDescription(),
                abs($entity->getChanges()),
                $entity->getUnit()->getDescriptor(),
                CommonProcessor::priceFilter($entity->getPrice())
            ),
            'filters' => [
                $entity->getClassName()               => $entity->getId()->__toString(),
                $entity->getVariant()->getClassName() => $entity->getVariant()->getId()->__toString(),
            ],
        ], LogsAuditCreateEvent::TYPE_STOCK_BALANCE_CHANGE));

        return $entity;
    }

    /**
     * @param Warehouse $warehouse
     * @return array
     */
    public function getWarehouseStockMap(Warehouse $warehouse): array
    {
        return $this->buildWarehouseVariantsMap($warehouse);
    }

    /**
     * @param WarehouseZone $warehouseZone
     * @return array
     */
    public function getWarehouseZoneStockMap(WarehouseZone $warehouseZone): array
    {
        return $this->buildZoneVariantsMap($warehouseZone);
    }

    /**
     * @param Warehouse $warehouse
     * @return array
     */
    private function buildWarehouseVariantsMap(Warehouse $warehouse): array
    {
        $warehouseData = [
            'title' => $warehouse->getTitle(),
            'zones' => [],
        ];

        /** @var WarehouseZone $zone */
        foreach ($warehouse->getZones() as $zone) {
            if ($zone->getParent()) {
                continue;
            }

            $zoneId = $zone->getId()->__toString();
            $warehouseData['zones'][$zoneId] = $this->buildZoneVariantsMap($zone);
        }

        return $warehouseData;
    }

    /**
     * @param WarehouseZone $zone
     * @param bool $includeVariants
     * @return array
     */
    private function buildZoneVariantsMap(WarehouseZone $zone, $includeVariants = true): array
    {
        $zoneData = [
            'id'            => $zone->getId()->__toString(),
            'title'         => $zone->getTitle(),
            'path'          => $zone->getPath(),
            'parentId'      => $zone->getParent() ? $zone->getParent()->getId()->__toString() : '',
            'parentTitle'   => $zone->getParent() ? $zone->getParent()->getTitle() : '',
            'subZones'      => [],
            'variantsCount' => 0,
        ];

        $revisionRepo = $this->entityManager->getRepository(StockRevision::class);

        $variants = $this->entityManager->getRepository(ProductVariant::class)->getZoneVariants($zone);
        if ($includeVariants) {
            $zoneData['variants'] = [];
            /** @var ProductVariant $variant */
            foreach ($variants as $variant) {
                $variantId = $variant->getId()->__toString();

                $zoneData['variants'][$variantId] = $variant->getDocument();
                $zoneData['variants'][$variantId]['revisions'] = [];

                $revisions = $revisionRepo->getVariantZoneRevisions($variant, $zone, [StockRevision::STATE_PLANNED, StockRevision::STATE_RECALCULATING]);
                /** @var StockRevision $revision */
                foreach ($revisions as $revision) {
                    $zoneData['variants'][$variantId]['revisions'][] = $revision->getApiFields();
                }

                // get last finished revision
                $zoneData['variants'][$variantId]['lastFinishedRevision'] = null;

                $lastRevision = $revisionRepo->getLastFinishedVariantRevision($variant, $zone);
                if ($lastRevision instanceof StockRevision) {
                    $zoneData['variants'][$variantId]['lastFinishedRevision'] = $lastRevision->getApiFields();
                }
            }
        }

        $zoneData['variantsCount'] = count($variants);

        /** @var WarehouseZone $child */
        foreach ($zone->getChildren() as $child) {
            $childId = $child->getId()->__toString();
            $zoneData['subZones'][$childId] = $this->buildZoneVariantsMap($child, false);
        }

        return $zoneData;
    }

    /**
     * @param ProductVariant $productVariant
     * @param Unit|null $unit
     * @return array
     */
    public function getProductVariantBalance(ProductVariant $productVariant, Unit $unit = null): array
    {
        if (!$unit) {
            $unit = $productVariant->getProduct()->getDefaultUnit();
        }

        $balance = [
            'balance' => [
                'value' => $this->calculateVariantStockBalance($productVariant, $unit),
                'unit'  => $unit->getDescriptor(),
            ],
            'reserve' => [
                'value' => $this->calculateVariantReserveBalance($productVariant, $unit),
                'unit'  => $unit->getDescriptor(),
            ],
        ];

        return $balance;
    }

    /**
     * @param ProductVariant $variant
     */
    public function refreshVariantStockBalance(ProductVariant $variant)
    {
        $balance = $this->calculateVariantStockBalance($variant, $variant->getProduct()->getDefaultUnit());
        $variant->setOnStock($balance);

        $this->entityManager->persist($variant);
        $this->entityManager->flush();
    }

    /**
     * @param ProductVariant $variant
     * @param Unit $unit
     * @return int
     */
    private function calculateVariantStockBalance(ProductVariant $variant, Unit $unit)
    {
        $stockHistoryRepository = $this->entityManager->getRepository(StockHistory::class);
        $stockHistories = $stockHistoryRepository->getProductVariantStockHistories($variant);

        $balance = 0;
        /** @var StockHistory $history */
        foreach ($stockHistories as $history) {
            $multiplier = $this->getMultiplierFromHashTable($history->getUnit(), $unit);
            $historyBalance = $history->getChanges() * $multiplier;

            $balance += $historyBalance;
        }

        return $balance;
    }

    /**
     * @param ProductVariant $variant
     * @param Unit $unit
     * @return int
     */
    private function calculateVariantReserveBalance(ProductVariant $variant, Unit $unit)
    {
        $stockReserveRepository = $this->entityManager->getRepository(StockReservation::class);
        $stockReserves = $stockReserveRepository->getProductVariantStockReserves($variant);

        $balance = 0;
        /** @var StockReservation $reserve */
        foreach ($stockReserves as $reserve) {
            $multiplier = $this->getMultiplierFromHashTable($reserve->getUnit(), $unit);
            $reserveBalance = $reserve->getChanges() * $multiplier;

            $balance += $reserveBalance;
        }

        return $balance;
    }

    /**
     * @param Product $product
     * @param Unit|null $unit
     * @return array
     */
    public function getProductBalance(Product $product, Unit $unit = null): array
    {
        if (!$unit) {
            $unit = $product->getDefaultUnit();
        }

        $balance = [
            'balance' => [
                'value' => $this->calculateProductStockBalance($product, $unit),
                'unit'  => $unit->getDescriptor(),
            ],
            'reserve' => [
                'value' => $this->calculateProductStockReserve($product, $unit),
                'unit'  => $unit->getDescriptor(),
            ],
        ];

        return $balance;
    }

    /**
     * @param Product $product
     * @param Unit $unit
     * @return int
     */
    private function calculateProductStockBalance(Product $product, Unit $unit)
    {
        $stockHistoryRepository = $this->entityManager->getRepository(StockHistory::class);
        $stockHistories = $stockHistoryRepository->getProductStockHistories($product);

        $balance = 0;
        /** @var StockHistory $history */
        foreach ($stockHistories as $history) {
            $multiplier = $this->getMultiplierFromHashTable($history->getUnit(), $unit);
            $historyBalance = $history->getChanges() * $multiplier;

            $balance += $historyBalance;
        }

        return $balance;
    }

    /**
     * @param Product $product
     * @param Unit $unit
     * @return int
     */
    private function calculateProductStockReserve(Product $product, Unit $unit)
    {
        $stockReserveRepository = $this->entityManager->getRepository(StockReservation::class);
        $stockReserves = $stockReserveRepository->getProductStockReserves($product);

        $balance = 0;
        /** @var StockReservation $reserve */
        foreach ($stockReserves as $reserve) {
            $multiplier = $this->getMultiplierFromHashTable($reserve->getUnit(), $unit);
            $reserveBalance = $reserve->getChanges() * $multiplier;

            $balance += $reserveBalance;
        }

        return $balance;
    }

    /**
     * @param StockHistory $stockHistory
     * @param Unit|null $unit
     * @return array
     */
    public function getStockHistoryBalance(StockHistory $stockHistory, Unit $unit = null)
    {
        if (!$unit) {
            $unit = $stockHistory->getVariant()->getProduct()->getDefaultUnit();
        }

        $balance = [
            'balance' => [
                'value' => $this->calculateStockHistoryBalance($stockHistory, $unit, true),
                'unit'  => $unit->getDescriptor(),
            ],
            'reserve' => [
                'value' => $this->calculateStockHistoryReserveBalance($stockHistory, $unit),
                'unit'  => $unit->getDescriptor(),
            ],
        ];

        return $balance;
    }

    /**
     * @param StockHistory $stockHistory
     * @param Unit $unit
     * @param bool $excludeReservations
     * @return float|int
     */
    private function calculateStockHistoryBalance(StockHistory $stockHistory, Unit $unit, $excludeReservations = false)
    {
        $stockHistoryRepository = $this->entityManager->getRepository(StockHistory::class);
        $stockHistories = $stockHistoryRepository->findChildHistories($stockHistory);

        $multiplier = $this->getMultiplierFromHashTable($stockHistory->getUnit(), $unit);
        $balance = $stockHistory->getChanges() * $multiplier;

        /** @var StockHistory $history */
        foreach ($stockHistories as $history) {
            $multiplier = $this->getMultiplierFromHashTable($history->getUnit(), $unit);
            $historyBalance = $history->getChanges() * $multiplier;

            $balance += $historyBalance;
        }

        if ($excludeReservations) {
            return $balance;
        }

        /** @var StockReservation $reservation */
        foreach ($stockHistory->getStockReservations() as $reservation) {
            $multiplier = $this->getMultiplierFromHashTable($reservation->getUnit(), $unit);
            $historyBalance = $reservation->getChanges() * $multiplier;

            $balance -= $historyBalance;
        }

        return $balance;
    }

    /**
     * @param StockHistory $stockHistory
     * @param Unit $unit
     * @return float|int
     */
    private function calculateStockHistoryReserveBalance(StockHistory $stockHistory, Unit $unit)
    {
        $balance = 0;
        /** @var StockReservation $reservation */
        foreach ($stockHistory->getStockReservations() as $reservation) {
            $multiplier = $this->getMultiplierFromHashTable($reservation->getUnit(), $unit);
            $historyBalance = $reservation->getChanges() * $multiplier;

            $balance += $historyBalance;
        }

        return $balance;
    }

    private function buildUnitEqualTrees()
    {
        $productUnitEquals = $this->buildProductEqualsMap();

        /** @var UnitEqual $item */
        foreach ($productUnitEquals as $item) {
            $this->buildUnitEqualsTreeFromTop($productUnitEquals, $item['saleUnit']);

            $this->buildUnitEqualsTreeFromBottom($productUnitEquals, $item['saleUnit']);
        }
    }

    /**
     * @param array $unitEquals
     * @param Unit $startUnit
     * @param Unit|null $lastStorageUnit
     * @return int
     */
    private function buildUnitEqualsTreeFromTop($unitEquals = [], Unit $startUnit, Unit &$lastStorageUnit = null)
    {
        $found = null;
        $multiplier = 1;

        /** @var UnitEqual $item */
        foreach ($unitEquals as $index => $item) {
            if ($startUnit->getId()->toString() === $item['saleUnit']->getId()->toString()) {
                $found = $item['storageUnit'];

                $lastStorageUnit = $found;

                $multiplier = $item['equal'];
                unset($unitEquals[$index]);
                break;
            }
        }

        if ($found instanceof Unit) {
            $this->addUnitEqualHash($startUnit, $found, $multiplier);

            $multiplier *= $this->buildUnitEqualsTreeFromTop($unitEquals, $found, $lastStorageUnit);

            if ($lastStorageUnit && $found->getId()->toString() !== $lastStorageUnit->getId()->toString()) {
                $this->addUnitEqualHash($startUnit, $lastStorageUnit, $multiplier);
            }
        }

        return $multiplier;
    }

    /**
     * @param array $unitEquals
     * @param Unit $startUnit
     * @param Unit|null $lastStorageUnit
     * @return float|int
     */
    private function buildUnitEqualsTreeFromBottom($unitEquals = [], Unit $startUnit, Unit &$lastStorageUnit = null)
    {
        $found = null;
        $multiplier = 1;

        /** @var UnitEqual $item */
        foreach ($unitEquals as $index => $item) {
            if ($startUnit->getId()->toString() === $item['storageUnit']->getId()->toString()) {
                $found = $item['saleUnit'];

                $lastStorageUnit = $found;

                $multiplier = 1 / $item['equal'];
                unset($unitEquals[$index]);
                break;
            }
        }

        if ($found instanceof Unit) {
            $this->addUnitEqualHash($startUnit, $found, $multiplier);

            $multiplier *= $this->buildUnitEqualsTreeFromBottom($unitEquals, $found, $lastStorageUnit);

            if ($lastStorageUnit && $found->getId()->toString() !== $lastStorageUnit->getId()->toString()) {
                $this->addUnitEqualHash($startUnit, $lastStorageUnit, $multiplier);
            }
        }

        return $multiplier;
    }

    /**
     * @param Unit $startUnit
     * @param Unit $baseUnit
     * @param $multiplier
     */
    private function addUnitEqualHash(Unit $startUnit, Unit $baseUnit, &$multiplier)
    {
        $pairsKey = $this->buildUnitEqualHashKey($startUnit, $baseUnit);

        if (isset(self::$unitEqualsMap[$pairsKey])) {
            $multiplier = self::$unitEqualsMap[$pairsKey]['equal'];
        }

        self::$unitEqualsHashTable[$pairsKey] = $multiplier;
    }

    /**
     * @param Unit $startUnit
     * @param Unit $baseUnit
     * @return float
     */
    private function getMultiplierFromHashTable(Unit $startUnit, Unit $baseUnit): float
    {
        $key = $this->buildUnitEqualHashKey($startUnit, $baseUnit);
        $multiplier = $this->getUnitEqualsHashed($key);

        if ($startUnit->getId()->toString() === $baseUnit->getId()->toString()) {
            $multiplier = 1;
        }

        if ($multiplier === 0) {
            // todo throw exception unit equal pair is not found
//            $multiplier = 1;
        }

        return $multiplier;
    }

    /**
     * @param $key
     * @return mixed
     */
    private function getUnitEqualsHashed($key)
    {
        if (self::$unitEqualsHashTable === null) {
            $this->buildUnitEqualTrees();
        }

        if (isset(self::$unitEqualsHashTable[$key])) {
            return self::$unitEqualsHashTable[$key];
        }

        return 0;
    }

    /**
     * @param Unit $startUnit
     * @param Unit $baseUnit
     * @return string
     */
    private function buildUnitEqualHashKey(Unit $startUnit, Unit $baseUnit)
    {
        $pairsKey = $startUnit->getDescriptor() . '|' . $baseUnit->getDescriptor();

        return $pairsKey;
    }

    /**
     * @param array|UnitEqual[] $unitEquals
     * @param Unit $startUnit
     * @param Unit|null $baseUnit
     * @return float
     */
    private function findUnitMultiplier($unitEquals = [], Unit $startUnit, Unit $baseUnit = null): float
    {
        $found = null;
        $multiplier = 1;

        if ($baseUnit && $startUnit->getId() === $baseUnit->getId()) {
            return $multiplier;
        }

        /** @var UnitEqual $item */
        foreach ($unitEquals as $index => $item) {
            if ($startUnit->getId() === $item->getSaleUnit()->getId()) {
                $found = $item->getStorageUnit();

                $multiplier = $item->getEqual();
                unset($unitEquals[$index]);
                break;
            }
            else {
                if ($startUnit->getId() === $item->getStorageUnit()->getId()) {
                    $found = $item->getSaleUnit();

                    $multiplier = 1 / $item->getEqual();
                    unset($unitEquals[$index]);
                    break;
                }
            }
        }

        if ($found instanceof Unit) {
            $multiplier *= $this->findUnitMultiplier($unitEquals, $found, $baseUnit);
        }

        return $multiplier;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function checkMissingUnitEquals(Product $product): bool
    {
        $missing = false;
        $usedUnits = $this->getProductStockUnitIds($product);
        $availableUnits = $this->getAvailableProductUnitIds($product);

        foreach ($usedUnits as $usedUnit) {
            if (!in_array($usedUnit, $availableUnits)) {
                $missing = true;
            }
        }

        return $missing;
    }

    /**
     * @param ProductVariant $variant
     * @return array
     */
    public function getVariantStockUnits(ProductVariant $variant)
    {
        $list = [];
        /** @var StockHistory $stockHistory */
        foreach ($variant->getStockHistories() as $stockHistory) {
            if (!in_array($stockHistory->getUnit()->getId()->toString(), $list)) {
                $list[] = $stockHistory->getUnit()->getId()->toString();
            }
        }

        return $list;
    }

    /**
     * @param Product $product
     * @return array
     */
    private function getAvailableProductUnitIds(Product $product)
    {
        $availableUnits = [];
        /** @var Unit $unit */
        foreach ($product->getUnits() as $unit) {
            $availableUnits[] = $unit->getId()->toString();
        }

        $unitRepository = $this->entityManager->getRepository(Unit::class);
        /** @var Unit $unit */
        foreach ($unitRepository->getUserStandardUnits($this->commonProcessor->getUserUUID()) as $unit) {
            $availableUnits[] = $unit->getId()->toString();
        }

        return $availableUnits;
    }

    /**
     * @param Product $product
     * @return array
     */
    private function getProductStockUnitIds(Product $product)
    {
        $list = [];
        /** @var ProductVariant $variant */
        foreach ($product->getVariants() as $variant) {
            $variantUnits = $this->getVariantStockUnits($variant);
            foreach ($variantUnits as $variantUnit) {
                if (!in_array($variantUnit, $list)) {
                    $list[] = $variantUnit;
                }
            }
        }

        return $list;
    }

    /**
     * @return array
     */
    private function buildProductEqualsMap(): array
    {
        $unitEqualsRepository = $this->entityManager->getRepository(UnitEqual::class);

        $productUnitEquals = $unitEqualsRepository->findAllForUser($this->commonProcessor->getUserUUID());
        self::$unitEqualsMap = [];
        // direct map
        /** @var UnitEqual $productUnitEqual */
        foreach ($productUnitEquals as $productUnitEqual) {
            $key = $this->buildUnitEqualHashKey($productUnitEqual->getSaleUnit(), $productUnitEqual->getStorageUnit());
            self::$unitEqualsMap[$key] = [
                'equal'       => $productUnitEqual->getEqual(),
                'saleUnit'    => $productUnitEqual->getSaleUnit(),
                'storageUnit' => $productUnitEqual->getStorageUnit(),
            ];
        }

        // reverse map
        /** @var UnitEqual $productUnitEqual */
        foreach ($productUnitEquals as $productUnitEqual) {
            $key = $this->buildUnitEqualHashKey($productUnitEqual->getStorageUnit(), $productUnitEqual->getSaleUnit());
            self::$unitEqualsMap[$key] = [
                'equal'       => 1 / $productUnitEqual->getEqual(),
                'saleUnit'    => $productUnitEqual->getStorageUnit(),
                'storageUnit' => $productUnitEqual->getSaleUnit(),
            ];
        }

        return self::$unitEqualsMap;
    }

    /**
     * @param ProductVariant $variant
     * @param $qty
     * @param Unit $unit
     * @param bool $excludeReservations
     * @return array|null
     */
    public function findEnoughStocks(ProductVariant $variant, $qty, Unit $unit, $excludeReservations = false)
    {
        $query = $this->entityManager->getRepository(StockHistory::class)->createQueryBuilder('sh');

        $stockHistories = $query->andWhere('sh.variant = :variant')
            ->andWhere('sh.changes > 0')
            ->orderBy('sh.createdAt', 'ASC')
            ->setParameter('variant', $variant)
            ->getQuery()->getResult();

        // we can use parent stock id to find returns by original stock id date
        $sum = 0;
        $stockHistoriesSorted = [];
        /** @var StockHistory $stockHistory */
        foreach ($stockHistories as $stockHistory) {
            $balance = $this->calculateStockHistoryBalance($stockHistory, $unit, $excludeReservations);
            if ($balance <= 0) {
                continue;
            }

            $sum += $balance;

            if ($stockHistory->getOriginStockHistory() instanceof StockHistory) {
                $timestamp = $stockHistory->getOriginStockHistory()->getCreatedAt()->getTimestamp();
            }
            else {
                $timestamp = $stockHistory->getCreatedAt()->getTimestamp();
            }

            if (isset($stockHistoriesSorted[$timestamp])) {
                $timestamp .= $stockHistory->getId()->toString();
            }

            $stockHistoriesSorted[$timestamp] = $stockHistory;
        }

        if ($sum < $qty) {
            return null;
        }

        ksort($stockHistoriesSorted);

        return $stockHistoriesSorted;
    }

    /**
     * @param $externalItemId
     * @param Unit $unit
     * @return number
     */
    private function countItemStockDeducted($externalItemId, Unit $unit)
    {
        $query = $this->entityManager->getRepository(StockHistory::class)->createQueryBuilder('sh');
        $stockHistories = $query->andWhere('sh.externalItemId = :externalItemId')
            ->setParameter('externalItemId', $externalItemId)
            ->getQuery()->getResult();
        $stockDeducted = 0;
        /** @var StockHistory $stockHistory */
        foreach ($stockHistories as $stockHistory) {
            $multiplier = $this->getMultiplierFromHashTable($stockHistory->getUnit(), $unit);
            $balance = $stockHistory->getChanges() * $multiplier;

            $stockDeducted += $balance;
        }

        return abs($stockDeducted);
    }

    /**
     * @param StockHistory $newStockHistory
     * @param StockHistory $stockHistory
     * @param $qtyCircle
     * @return int
     */
    private function processSubStockChanges(StockHistory $newStockHistory, StockHistory $stockHistory, &$qtyCircle)
    {
        $newStockHistory->setPrice($stockHistory->getPrice());
        $newStockHistory->setOriginStockHistory($stockHistory);
        $newStockHistory->setWarehouse($stockHistory->getWarehouse());
        $newStockHistory->setWarehouseZone($stockHistory->getWarehouseZone());

        $balance = $this->calculateStockHistoryBalance($stockHistory, $newStockHistory->getUnit(), true);

        $diff = $balance - $qtyCircle;

        if ($diff >= 0) {
            $newStockHistory->setChanges(-$qtyCircle);
        }
        else {
            $newStockHistory->setChanges(-$balance);

            $qtyCircle = abs($diff);
        }

        $this->entityManager->persist($newStockHistory);

        return $diff;
    }

    /**
     * @param StockHistory $stockHistory
     * @return bool
     */
    public function revertStock(StockHistory $stockHistory)
    {
        $variant = $stockHistory->getVariant();

        // check if items from this certain stock was already deducted in other stock
        $result = $this->getStocksDeducted($stockHistory);
        if ($result) {
            /** @var StockHistory $deductedStockHistory */
            foreach ($result as $deductedStockHistory) {
                // try to deduct it from current available stock
                $this->revertStock($deductedStockHistory);
            }
        }

        $logData = [
            'message' => sprintf(
                'Запись о %s товара "%s" %s %s со склада по цене %s грн. УДАЛЕНА!',
                $stockHistory->getChanges() > 0 ? 'добавлении' : 'списании',
                $variant->getDescription(),
                abs($stockHistory->getChanges()),
                $stockHistory->getUnit()->getDescriptor(),
                CommonProcessor::priceFilter($stockHistory->getPrice())
            ),
            'filters' => [
                $stockHistory->getClassName() => $stockHistory->getId()->__toString(),
                $variant->getClassName()      => $variant->getId()->__toString(),
            ],
        ];

        $this->entityManager->persist($variant);
        $this->entityManager->remove($stockHistory);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(LogsAuditCreateEvent::NAME, new LogsAuditCreateEvent($logData, LogsAuditCreateEvent::TYPE_STOCK_BALANCE_CHANGE));

        $this->dispatcher->dispatch(VariantBalanceChangedEvent::NAME, new VariantBalanceChangedEvent($variant));

        return true;
    }

    /** check if items from this certain stock was already deducted in other stock */
    /**
     * @param StockHistory $stockHistory
     * @return array|StockHistory[]
     */
    public function getStocksDeducted(StockHistory $stockHistory)
    {
        return $this->entityManager->getRepository(StockHistory::class)->findBy([
            'originStockHistory' => $stockHistory,
        ]);
    }


    /********************* Legacy ***************************/

    /**
     * @param StockHistory $stockHistory
     * @return bool
     */
    public function returnStock(StockHistory $stockHistory)
    {
        if ($stockHistory->getChanges() > 0) {
            // process only substock operations
            return false;
        }

        // check if stock was already returned
        if ($stockHistory->getExternalItemId() !== null) {
            if ($this->getItemStockWasReturned($stockHistory->getExternalItemId())) {
                return true;
            }
        }

        $variant = $stockHistory->getVariant();

        $newStockHistory = new StockHistory();
        $newStockHistory->setChanges($stockHistory->getChanges() * (-1));
        $newStockHistory->setPrice($stockHistory->getPrice());
        $newStockHistory->setVariant($variant);
        $newStockHistory->setOriginStockHistory($stockHistory->getOriginStockHistory());
        $newStockHistory->setWarehouse($stockHistory->getWarehouse());
        $newStockHistory->setWarehouseZone($stockHistory->getWarehouseZone());

        $this->entityManager->persist($variant);
        $this->entityManager->persist($newStockHistory);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(VariantBalanceChangedEvent::NAME, new VariantBalanceChangedEvent($variant));

        $this->dispatcher->dispatch(LogsAuditCreateEvent::NAME, new LogsAuditCreateEvent([
            'message' => sprintf(
                'Товар "%s" %s %s списанный по цене %s грн. возвращен на склад',
                $variant->getDescription(),
                abs($stockHistory->getChanges()),
                $stockHistory->getUnit()->getDescriptor(),
                CommonProcessor::priceFilter($stockHistory->getPrice())
            ),
            'filters' => [
                $stockHistory->getClassName() => $stockHistory->getId()->__toString(),
                $variant->getClassName()      => $variant->getId()->__toString(),
            ],
        ], LogsAuditCreateEvent::TYPE_STOCK_BALANCE_CHANGE));

        return true;
    }

    /**
     * @param $externalItemId
     * @return bool
     */
    public function getItemStockWasReturned($externalItemId)
    {
        $query = $this->entityManager->getRepository(StockHistory::class)->createQueryBuilder('sh');
        $stockHistories = $query->andWhere('sh.externalItemId = :externalItemId')
            ->setParameter('externalItemId', $externalItemId)
            ->getQuery()->getResult();
        $stockDeducted = 0;
        /** @var StockHistory $stockHistory */
        foreach ($stockHistories as $stockHistory) {
            $stockDeducted += $stockHistory->getChanges();
        }

        return $stockDeducted == 0;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deleteStockHistoryById($id)
    {
        $stockHistory = $this->entityManager->getRepository(StockHistory::class)->find($id);
        if ($stockHistory instanceof StockHistory) {
            return $this->revertStock($stockHistory);
        }

        return false;
    }
}
