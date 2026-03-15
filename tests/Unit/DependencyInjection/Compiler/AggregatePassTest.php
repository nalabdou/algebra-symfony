<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Tests\Unit\DependencyInjection\Compiler;

use Nalabdou\Algebra\Aggregate\AggregateRegistry;
use Nalabdou\Algebra\Symfony\DependencyInjection\Compiler\AggregatePass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

#[CoversClass(AggregatePass::class)]
final class AggregatePassTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->container->register('algebra.aggregates', AggregateRegistry::class);
    }

    public function testRegistersTaggedAggregates(): void
    {
        $aggregate = new Definition('App\Aggregate\GeomeanAggregate');
        $aggregate->addTag('algebra.aggregate');
        $this->container->setDefinition('app.geomean_aggregate', $aggregate);

        (new AggregatePass())->process($this->container);

        $calls = $this->container->getDefinition('algebra.aggregates')->getMethodCalls();
        self::assertCount(1, $calls);
        self::assertSame('register', $calls[0][0]);
    }

    public function testRegistersMultipleAggregates(): void
    {
        foreach (['geomean', 'harmonic', 'iqr'] as $name) {
            $def = new Definition("App\\Aggregate\\{$name}Aggregate");
            $def->addTag('algebra.aggregate');
            $this->container->setDefinition("app.{$name}", $def);
        }

        (new AggregatePass())->process($this->container);

        $calls = $this->container->getDefinition('algebra.aggregates')->getMethodCalls();
        self::assertCount(3, $calls);
    }

    public function testNoTaggedServicesNoMethodCalls(): void
    {
        (new AggregatePass())->process($this->container);

        $calls = $this->container->getDefinition('algebra.aggregates')->getMethodCalls();
        self::assertCount(0, $calls);
    }

    public function testSkipsGracefullyWhenRegistryMissing(): void
    {
        $emptyContainer = new ContainerBuilder();

        // Must not throw
        (new AggregatePass())->process($emptyContainer);
        self::assertTrue(true);
    }
}
