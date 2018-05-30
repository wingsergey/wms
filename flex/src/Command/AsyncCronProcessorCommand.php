<?php

namespace App\Command;

use App\Service\AsyncProcessProcessor;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class AsyncCronProcessorCommand extends ContainerAwareCommand
{
    const ACTIVITY_TIMEOUT = 60;
    const PID_FILE = '/../var/cache/async_cron_processor.pid';
    /** @var AsyncProcessProcessor */
    private $asyncProcessor;

    protected $jobs = [
        ['expression' => '* * * * *', 'command' => 'wms:reserves:remove_expired', 'is_critical' => false],
    ];

    /**
     * AsyncCronProcessorCommand constructor.
     * @param AsyncProcessProcessor $asyncProcessProcessor
     */
    public function __construct(AsyncProcessProcessor $asyncProcessProcessor)
    {
        $this->asyncProcessor = $asyncProcessProcessor;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('valpio:cron:async_processor')
            ->setDescription('Run cron commands async');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();

        // check if we need to start new update instance
        $pidPath = $this->getContainer()->getParameter('kernel.root_dir') . self::PID_FILE;
        if (!$fs->exists($pidPath)) {
            $fs->touch($pidPath);
        } elseif (filemtime($pidPath) > (time() - self::ACTIVITY_TIMEOUT * 3)) {
            return;
        }

        while (true) {
            $loop_start = time();
            touch($pidPath);

            foreach ($this->jobs as $jobData) {
                $this->start_cron($jobData);
            }

            touch($pidPath);
            sleep($this->calculate_sleep_time($loop_start));
        }
    }

    /**
     * Start sub processes for cron jobs that meet expression
     * @param array $jobData
     * @return null|\Symfony\Component\Process\Process
     */
    protected function start_cron(array $jobData)
    {
        $cron = \Cron\CronExpression::factory($jobData['expression']);
        $process = null;
        if ($cron->isDue()) {
            $timeout = isset($jobData['timeout']) ? (int)$jobData['timeout'] : 0;
            $isCritical = isset($jobData['is_critical']) ? (bool)$jobData['is_critical'] : true;
            $threshold = isset($jobData['threshold']) ? (int)$jobData['threshold'] : 100;
            $this->asyncProcessor->addProcess([$jobData['command']], $timeout, $isCritical, $threshold);
        }

        return $process;
    }

    /**
     * Calculate seconds left to sleep to meet timeout
     *
     * @param $loop_start
     * @return int
     */
    protected function calculate_sleep_time($loop_start)
    {
        $loop_time_diff = self::ACTIVITY_TIMEOUT - (time() - $loop_start);
        $sleep_time = $loop_time_diff > 0 ? $loop_time_diff : 0;

        return $sleep_time;
    }
}
