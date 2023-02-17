<?php declare(strict_types=1);

namespace MoorlOrderFixer\Core\OrderFixer;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Console\Style\SymfonyStyle;

class OrderFixerTaskHandler extends ScheduledTaskHandler
{
    /**
     * @var OrderFixerService
     */
    private $service;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        OrderFixerService $service
    )
    {
        parent::__construct($scheduledTaskRepository);

        $this->service = $service;
    }

    public static function getHandledMessages(): iterable
    {
        return [OrderFixerTask::class];
    }

    public function run(?SymfonyStyle $console = null): void
    {
        $this->service->run($console);
    }
}
