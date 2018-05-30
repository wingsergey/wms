<?php

namespace App\Service;

use App\EventListener\LogsAuditCreateEvent;
use Exception;
use Psr\Log\LoggerAwareTrait;
use Valpio\ApiClientBundle\Service\ApiClient;

/**
 * Class LogsAuditProcessor
 * @package App\Service
 */
class LogsAuditProcessor
{
    use LoggerAwareTrait;

    /** @var ApiClient */
    protected $apiClient;

    /** @var array */
    protected $parameters;

    /** @var CommonProcessor */
    private $commonProcessor;

    /**
     * LogsAuditProcessor constructor.
     * @param ApiClient $apiClient
     * @param array $parameters
     * @param CommonProcessor $commonProcessor
     */
    public function __construct(ApiClient $apiClient, array $parameters, CommonProcessor $commonProcessor)
    {
        $this->commonProcessor = $commonProcessor;

        $this->parameters = $parameters;
        $this->apiClient  = $apiClient;
        $this->apiClient->setParameters($parameters);
    }

    /**
     * @param LogsAuditCreateEvent $logsAuditCreateEvent
     */
    public function saveEvent(LogsAuditCreateEvent $logsAuditCreateEvent) : void
    {
        $logData = [
            'channel'    => $this->parameters['channel'],
            'type'       => $logsAuditCreateEvent->getLogType(),
            'payload'    => json_encode($logsAuditCreateEvent->getData()),
            'date'       => $logsAuditCreateEvent->getLoggedAt(),
        ];

        $this->sendLog($logData);
    }

    /**
     * @param array $data
     */
    public function sendLog(array $data) : void
    {
        // check if url is set and we need to send log
        if (!$this->parameters['api_url']) {
            return;
        }

        try {
            $this->apiClient->send('log-entry/create', [], 'POST', $data,  [
                'User-ID' => $this->commonProcessor->getUserUUID()
            ]);
        } catch (Exception $e) {
            $this->logger->critical(self::class . '::Exception', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
