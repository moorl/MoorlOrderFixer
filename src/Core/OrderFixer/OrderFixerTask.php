<?php declare(strict_types=1);

namespace MoorlOrderFixer\Core\OrderFixer;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class OrderFixerTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'moorl_magazine.sync';
    }

    public static function getDefaultInterval(): int
    {
        return 600; // 10m
    }
}
