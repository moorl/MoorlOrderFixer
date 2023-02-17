<?php declare(strict_types=1);

namespace MoorlOrderFixer\Core\OrderFixer;

use Doctrine\DBAL\Connection;
use PackiroNumberFixer\PackiroNumberFixer;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\Adapter\Console\ShopwareStyle;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class OrderFixerService
{
    /**
     * @var DefinitionInstanceRegistry
     */
    private $definitionInstanceRegistry;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var NumberRangeValueGeneratorInterface
     */
    private $numberRangeValueGenerator;

    /**
     * @var Context
     */
    private $context;
    /**
     * @var SymfonyStyle|null
     */
    private $console;

    public function __construct(
        DefinitionInstanceRegistry $definitionInstanceRegistry,
        Connection $connection,
        NumberRangeValueGeneratorInterface $numberRangeValueGenerator
    )
    {
        $this->definitionInstanceRegistry = $definitionInstanceRegistry;
        $this->connection = $connection;
        $this->numberRangeValueGenerator = $numberRangeValueGenerator;

        $this->context = Context::createDefaultContext();
    }

    public function run(?SymfonyStyle $console = null): void
    {
        if (!$console) {
            $console = new ShopwareStyle(new ArgvInput(), new NullOutput());
        }
        $this->console = $console;

        /* Get all orders with no live version */
        $sql = <<<SQL
select 
       order_number, 
       count(case when version_id = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425') then 1 END) as counter 
from `order` 
group by order_number having count(case when version_id = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425') then 1 END) = 0;
SQL;
        $this->console->writeln($sql);
        $duplicates = $this->connection->fetchAll($sql);

        $this->console->writeln(sprintf("Found %s orders with no live version", count($duplicates)));

        foreach ($duplicates as $duplicate) {
            $this->console->writeln(sprintf("Process order number: %s", $duplicate['order_number']));
            /*$question = $this->console->askQuestion(new Question(sprintf("Process order number: %s?", $duplicate['order_number'])));
            if ($question !== "y") {
                continue;
            }*/

            /* Get all versions of single order, last edited order first */
            $sql = <<<SQL
SELECT 
       id,
       version_id
FROM `order`
WHERE order_number = :order_number
ORDER BY auto_increment DESC
SQL;
            $this->console->writeln($sql);
            $orderVersions = $this->connection->fetchAll($sql, [
                'order_number' => $duplicate['order_number']
            ]);

            /* Transform first order to live version */
            $orderVersion = array_shift($orderVersions);

            $sql = <<<SQL
UPDATE `order` SET `version_id` = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425') WHERE `id` = :id and `version_id` = :version_id;
UPDATE `order_address`  SET `version_id` = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425')  WHERE `order_id` = :id and `order_version_id` = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425');
UPDATE `order_customer` SET `version_id` = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425') WHERE `order_id` = :id and `order_version_id` = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425');
UPDATE `order_delivery` SET `version_id` = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425') WHERE `order_id` = :id and `order_version_id` = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425');
UPDATE `order_delivery_position` SET `version_id` = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425'), `order_delivery_version_id` = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425') WHERE order_delivery_id = (SELECT id FROM `order_delivery` WHERE `order_id` = :id and `order_version_id` = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425') and order_delivery_version_id = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425'));
UPDATE `order_line_item` SET `version_id` = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425') WHERE `order_id` = :id and `order_version_id` = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425');
UPDATE `order_transaction` SET `version_id` = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425') WHERE `order_id` = :id and `order_version_id` = UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425');
SQL;

            $this->connection->executeStatement($sql, [
                'id' => $orderVersion['id'],
                'version_id' => $orderVersion['version_id'],
            ]);
        }
    }
}
