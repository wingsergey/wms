<?php

namespace App\Command;

use App\Service\StockProcessor;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RemoveExpiredReservesCommand
 * @package App\Command
 */
class RemoveExpiredReservesCommand extends ContainerAwareCommand
{
    /** @var StockProcessor */
    private $stockProcessor;

    /**
     * RemoveExpiredReservesCommand constructor.
     * @param StockProcessor $stockProcessor
     */
    public function __construct(StockProcessor $stockProcessor)
    {
        $this->stockProcessor = $stockProcessor;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('wms:reserves:remove_expired')
            ->setDescription('Remove expired reserves');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->stockProcessor->removeExpiredReserves();
    }
}
