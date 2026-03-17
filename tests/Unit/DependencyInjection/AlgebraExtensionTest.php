<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Tests\Unit\DependencyInjection;

use Nalabdou\Algebra\Adapter\AdapterRegistry;
use Nalabdou\Algebra\Aggregate\AggregateRegistry;
use Nalabdou\Algebra\Collection\CollectionFactory;
use Nalabdou\Algebra\Expression\ExpressionEvaluator;
use Nalabdou\Algebra\Symfony\AlgebraBundle;
use Nalabdou\Algebra\Symfony\DependencyInjection\AlgebraExtension;
use Nalabdou\Algebra\Symfony\EventListener\AlgebraBootstrapListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(AlgebraExtension::class)]
#[CoversClass(AlgebraBundle::class)]
final class AlgebraExtensionTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        (new AlgebraExtension())->load([[]], $this->container);
    }

    public function testRegistersFactoryAsPublic(): void
    {
        self::assertTrue($this->container->hasDefinition('algebra.factory'));
        self::assertTrue($this->container->getDefinition('algebra.factory')->isPublic());
    }

    public function testRegistersEvaluatorAsPublic(): void
    {
        self::assertTrue($this->container->hasDefinition('algebra.evaluator'));
        self::assertTrue($this->container->getDefinition('algebra.evaluator')->isPublic());
    }

    public function testRegistersAggregatesAsPublic(): void
    {
        self::assertTrue($this->container->hasDefinition('algebra.aggregates'));
        self::assertTrue($this->container->getDefinition('algebra.aggregates')->isPublic());
    }

    public function testRegistersAdapterRegistryAsPublic(): void
    {
        self::assertTrue($this->container->hasDefinition('algebra.adapter_registry'));
        self::assertTrue($this->container->getDefinition('algebra.adapter_registry')->isPublic());
    }

    public function testAdapterRegistryUsesCorrectClass(): void
    {
        self::assertSame(
            AdapterRegistry::class,
            $this->container->getDefinition('algebra.adapter_registry')->getClass()
        );
    }

    public function testFactoryReceivesAdapterRegistry(): void
    {
        $args = $this->container->getDefinition('algebra.factory')->getArguments();
        self::assertArrayHasKey('$adapterRegistry', $args);
    }

    public function testRegistersPlannerAsPrivate(): void
    {
        self::assertTrue($this->container->hasDefinition('algebra.planner'));
        self::assertFalse($this->container->getDefinition('algebra.planner')->isPublic());
    }

    public function testRegistersBootstrapListener(): void
    {
        self::assertTrue($this->container->hasDefinition('algebra.bootstrap_listener'));
        self::assertSame(
            AlgebraBootstrapListener::class,
            $this->container->getDefinition('algebra.bootstrap_listener')->getClass()
        );
    }

    public function testBootstrapListenerEventTag(): void
    {
        $tags = $this->container->getDefinition('algebra.bootstrap_listener')->getTags();
        self::assertSame('kernel.request', $tags['kernel.event_listener'][0]['event']);
        self::assertSame('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
        self::assertSame(256, $tags['kernel.event_listener'][0]['priority']);
    }

    public function testBootstrapListenerHasAdaptersArgument(): void
    {
        $args = $this->container->getDefinition('algebra.bootstrap_listener')->getArguments();
        self::assertArrayHasKey('$adapters', $args);
    }

    public function testBootstrapListenerHasAggregatesArgument(): void
    {
        $args = $this->container->getDefinition('algebra.bootstrap_listener')->getArguments();
        self::assertArrayHasKey('$aggregates', $args);
    }

    public function testStrictModeDefaultsToTrue(): void
    {
        $args = $this->container->getDefinition('algebra.evaluator')->getArguments();
        self::assertTrue($args['$strictMode']);
    }

    public function testStrictModeConfigurableToFalse(): void
    {
        $container = new ContainerBuilder();
        (new AlgebraExtension())->load([['strict_mode' => false]], $container);

        $args = $container->getDefinition('algebra.evaluator')->getArguments();
        self::assertFalse($args['$strictMode']);
    }

    public function testFactoryUsesCorrectClass(): void
    {
        self::assertSame(CollectionFactory::class, $this->container->getDefinition('algebra.factory')->getClass());
    }

    public function testAggregatesUsesCorrectClass(): void
    {
        self::assertSame(AggregateRegistry::class, $this->container->getDefinition('algebra.aggregates')->getClass());
    }

    public function testEvaluatorUsesCorrectClass(): void
    {
        self::assertSame(ExpressionEvaluator::class, $this->container->getDefinition('algebra.evaluator')->getClass());
    }

    public function testExtensionAlias(): void
    {
        self::assertSame('algebra', (new AlgebraExtension())->getAlias());
    }
}
