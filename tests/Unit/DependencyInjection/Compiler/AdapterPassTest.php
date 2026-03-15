<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Tests\Unit\DependencyInjection\Compiler;

use Nalabdou\Algebra\Collection\CollectionFactory;
use Nalabdou\Algebra\Symfony\DependencyInjection\Compiler\AdapterPass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

#[CoversClass(AdapterPass::class)]
final class AdapterPassTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $factory = new Definition(CollectionFactory::class);
        $factory->setArgument('$adapters', []);
        $this->container->setDefinition('algebra.factory', $factory);
    }

    public function testInjectsTaggedAdaptersIntoFactory(): void
    {
        $adapter = new Definition('App\Adapter\CsvAdapter');
        $adapter->addTag('algebra.adapter', ['priority' => 50]);
        $this->container->setDefinition('app.csv_adapter', $adapter);

        (new AdapterPass())->process($this->container);

        $args = $this->container->getDefinition('algebra.factory')->getArguments();
        self::assertCount(1, $args['$adapters']);
    }

    public function testOrdersByPriorityDescending(): void
    {
        foreach ([['low', 10], ['high', 100], ['mid', 50]] as [$name, $priority]) {
            $def = new Definition("App\\Adapter\\{$name}Adapter");
            $def->addTag('algebra.adapter', ['priority' => $priority]);
            $this->container->setDefinition("app.{$name}_adapter", $def);
        }

        (new AdapterPass())->process($this->container);

        $adapters = $this->container->getDefinition('algebra.factory')->getArguments()['$adapters'];
        self::assertCount(3, $adapters);
    }

    public function testNoAdaptersLeavesFactoryUnchanged(): void
    {
        (new AdapterPass())->process($this->container);

        $args = $this->container->getDefinition('algebra.factory')->getArguments();
        self::assertEmpty($args['$adapters']);
    }

    public function testSkipsGracefullyWhenFactoryMissing(): void
    {
        $emptyContainer = new ContainerBuilder();

        (new AdapterPass())->process($emptyContainer);
        self::assertTrue(true);
    }
}
