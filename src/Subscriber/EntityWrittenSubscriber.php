<?php declare(strict_types=1);

namespace MoorlOrderFixer\Subscriber;

use MoorlOrderFixer\Core\OrderFixer\OrderFixerService;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EntityWrittenSubscriber implements EventSubscriberInterface
{
    /**
     * @var OrderFixerService
     */
    private $numberFixerService;

    public function __construct(OrderFixerService $numberFixerService)
    {
        $this->numberFixerService = $numberFixerService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityWrittenContainerEvent::class => 'onEntityWrittenContainerEvent'
        ];
    }

    public function onEntityWrittenContainerEvent(EntityWrittenContainerEvent $event): void
    {
        foreach ($event->getEvents() as $entityWrittenEvent) {
            if ($entityWrittenEvent instanceof EntityWrittenEvent) {
                if ($entityWrittenEvent->getEntityName() !== CustomerDefinition::ENTITY_NAME) {
                    continue;
                }

                foreach ($entityWrittenEvent->getPayloads() as $payload) {
                    $this->numberFixerService->checkPayload($payload);
                }
            }
        }
    }
}
