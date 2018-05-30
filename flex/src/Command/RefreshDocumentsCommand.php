<?php

namespace App\Command;

use App\Service\DocumentsProcessor;
use App\Service\StockProcessor;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RefreshDocumentsCommand
 * @package App\Command
 */
class RefreshDocumentsCommand extends ContainerAwareCommand
{
    /** @var DocumentsProcessor */
    private $documentsProcessor;

    /**
     * RefreshDocumentsCommand constructor.
     * @param DocumentsProcessor $documentsProcessor
     */
    public function __construct(DocumentsProcessor $documentsProcessor)
    {
        $this->documentsProcessor = $documentsProcessor;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('wms:documents:refresh')
            ->setDescription('Refresh cached documents');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->documentsProcessor->refreshEntities();
    }
}
