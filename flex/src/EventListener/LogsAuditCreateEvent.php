<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class LogsAuditCreateEvent
 * @package App\EventListener
 */
class LogsAuditCreateEvent extends Event
{
    const NAME = 'logs.audit.create';

    const TYPE_STOCK_RESERVATION = 'stock_reservation';
    const TYPE_STOCK_REVISION = 'stock_revision';
    const TYPE_STOCK_BALANCE_CHANGE = 'stock_balance_change';

    /** @var array */
    protected $data;

    /** @var string */
    protected $type;

    /** @var int */
    protected $loggedAt;

    /**
     * LogsAuditCreateEvent constructor.
     * @param $data
     * @param string $logType
     */
    public function __construct($data, $logType = '')
    {
        $this->data     = $data;
        $this->type     = $logType;
        $this->loggedAt = time();
    }

    /**
     * @return array
     */
    public function getData() : array
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getLoggedAt() : int
    {
        return $this->loggedAt;
    }

    /**
     * @return string
     */
    public function getLogType() : string
    {
        return $this->type;
    }
}