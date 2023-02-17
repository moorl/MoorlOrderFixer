<?php declare(strict_types=1);

namespace MoorlOrderFixer\Core\OrderFixer;

use Shopware\Core\Framework\Adapter\Console\ShopwareStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class OrderFixerCommand extends Command
{
    protected static $defaultName = 'moorl:order-fixer';

    /**
     * @var OrderFixerTaskHandler
     */
    private $taskHandler;

    public function __construct(OrderFixerTaskHandler $taskHandler)
    {
        parent::__construct('moorl.order-fixer');

        $this->taskHandler = $taskHandler;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new ShopwareStyle($input, $output);
        $io->title('Order Fixer');

        $this->taskHandler->run($io);

        return 1;
    }
}
