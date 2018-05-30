<?php
namespace App\Service;

use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Class AsyncProcessProcessor
 * @package App\Service
 */
class AsyncProcessProcessor
{
    const SUB_PROCESSES_TIME_LIMIT = 600;
    const SUB_PROCESSES_IDLE_TIME_LIMIT = 90;
    static $cpu_cores_qty = 1;

    /** @var array */
    protected $parameters;
    /** @var array list of current processes */
    private $processes = [];

    /**
     * @param $parameters
     */
    public function __construct($parameters)
    {
        $this->parameters = $parameters;
        $this->getCpusQty();
    }

    /**
     * @param array $commands
     * @param int $timeout
     * @param bool $is_critical
     * @param float $cpu_threshold
     * @return bool|mixed
     */
    public function addProcess(array $commands, $timeout = 0, $is_critical = true, $cpu_threshold = 100.0)
    {
        $this->cleanFinishedProcesses();

        if ($this->checkProcessExist($commands)) {
            return false;
        }

        // check server load
        if (!$this->checkStartAllowed($is_critical, $cpu_threshold)) {
            return false;
        }

        $phpExecutableFinder = new PhpExecutableFinder();

        $arguments = [
            $phpExecutableFinder->find(),
            $this->parameters['root_dir'] . '/console',
        ];
        foreach ($commands as $command) {
            $arguments[] = $command;
        }
        if (!in_array('--env=prod', $arguments)) {
            $arguments[] = '--env=prod';
        }
        if (!in_array('--no-debug', $arguments)) {
            $arguments[] = '--no-debug';
        }

        $processName = $this->getProcessName($commands);
        $processBuilder = new ProcessBuilder($arguments);
        $this->processes[$processName] = $processBuilder->getProcess();
        $this->processes[$processName]->setTimeout($timeout !== null ? (int)$timeout : self::SUB_PROCESSES_TIME_LIMIT);
        //        $this->processes[$processName]->setIdleTimeout(self::SUB_PROCESSES_IDLE_TIME_LIMIT);
        $this->processes[$processName]->start();

        /* // code for testing while develop
        $this->processes[$processName]->wait(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'ERR > '.$buffer;
            } else {
                echo 'OUT > '.$buffer;
            }
        });
        */

        return $this->processes[$processName];
    }

    /**
     * Remove finished processes from current processes list
     */
    public function cleanFinishedProcesses()
    {
        /** @var Process $process */
        foreach ($this->processes as $processName => $process) {
            $process->checkTimeout();
            if (!$process->isRunning()) {
                unset($this->processes[$processName]);
            }
        }
    }

    /**
     * Check if process already registered
     * @param  array $commands
     * @return bool
     */
    private function checkProcessExist(array $commands)
    {
        return isset($this->processes[$this->getProcessName($commands)]);
    }

    /**
     * @param  array $commands
     * @return string unique process identifier
     */
    private function getProcessName(array $commands)
    {
        return md5(serialize($commands));
    }

    /**
     * @param bool $is_critical
     * @param float $cpu_threshold
     * @return bool
     */
    private function checkStartAllowed($is_critical = true, $cpu_threshold = 100.0)
    {
        if ($is_critical) {
            return true;
        }

        $serverLoad = self::getServerLoad();

        if ($cpu_threshold != 100.0) {
            $loadPercent = round($serverLoad * 100 / self::$cpu_cores_qty, 2);

            return $loadPercent <= $cpu_threshold;
        }


        if ($serverLoad !== null && $serverLoad > self::$cpu_cores_qty) {
            return false;
        }

        return true;
    }

    /**
     * Returns server load in percent (just number, without percent sign)
     * @return float|null
     */
    public static function getServerLoad()
    {
        $load = null;
        if (stristr(PHP_OS, 'win')) {
            // enable php_com_dotnet.dll in php.ini
            if (!class_exists('COM')) {
                return $load;
            }

            $wmi = new \COM("Winmgmts://");
            $server = $wmi->execquery("SELECT LoadPercentage FROM Win32_Processor");

            $cpu_num = 0;
            $load_total = 0;
            foreach ($server as $cpu) {
                $cpu_num++;
                $load_total += $cpu->loadpercentage;
            }

            $load = round($load_total / $cpu_num, 2);
        } else {
            $sys_load = sys_getloadavg();
            $load = $sys_load[0];
        }

        return (float)$load;
    }


    /**
     * Return number of logic CPUs
     *
     * @return int
     */
    public static function getCpusQty()
    {
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            self::$cpu_cores_qty = count($matches[0]);
        } else if ('WIN' == strtoupper(substr(PHP_OS, 0, 3))) {
            $process = @popen('wmic cpu get NumberOfCores', 'rb');
            if (false !== $process) {
                fgets($process);
                self::$cpu_cores_qty = intval(fgets($process));
                pclose($process);
            }
        } else {
            $process = @popen('sysctl -a', 'rb');
            if (false !== $process) {
                $output = stream_get_contents($process);
                preg_match('/hw.ncpu: (\d+)/', $output, $matches);
                if ($matches) {
                    self::$cpu_cores_qty = intval($matches[1][0]);
                }
                pclose($process);
            }
        }

        return self::$cpu_cores_qty;
    }
}
