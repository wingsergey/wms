<?php

namespace App\EventListener;

use App\Service\StockProcessor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class BalanceChangedSubscriber
 * @package App\Event
 */
class BalanceChangedSubscriber implements EventSubscriberInterface
{
    /** @var StockProcessor */
    protected $stockProcessor;

    /**
     * BalanceChangedSubscriber constructor.
     * @param StockProcessor $stockProcessor
     */
    public function __construct(StockProcessor $stockProcessor)
    {
        $this->stockProcessor = $stockProcessor;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents() : array
    {
        return [
            VariantBalanceChangedEvent::NAME => 'onVariantBalanceChanged',
            VariantReserveChangedEvent::NAME => 'onVariantReserveChanged',
        ];
    }

    /**
     * @param VariantBalanceChangedEvent $event
     */
    public function onVariantBalanceChanged(VariantBalanceChangedEvent $event)
    {
        $this->stockProcessor->refreshVariantStockBalance($event->getProductVariant());
    }

    /**
     * @param VariantReserveChangedEvent $event
     */
    public function onVariantReserveChanged(VariantReserveChangedEvent $event)
    {
        $this->stockProcessor->refreshVariantReserveBalance($event->getProductVariant());
    }
}