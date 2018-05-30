<?php

namespace App\EventListener;

use App\Service\LogsAuditProcessor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LogsAuditSubscriber
 * @package App\EventListener
 */
class LogsAuditSubscriber implements EventSubscriberInterface
{
    /** @var LogsAuditProcessor */
    protected $logsAuditProcessor;

    /**
     * LogsAuditSubscriber constructor.
     * @param LogsAuditProcessor $logsAuditProcessor
     */
    public function __construct(LogsAuditProcessor $logsAuditProcessor)
    {
        $this->logsAuditProcessor = $logsAuditProcessor;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents() : array
    {
        return [
            LogsAuditCreateEvent::NAME => 'sendLog',
        ];
    }

    /**
     * @param LogsAuditCreateEvent $event
     */
    public function sendLog(LogsAuditCreateEvent $event)
    {
        $this->logsAuditProcessor->saveEvent($event);
    }
}