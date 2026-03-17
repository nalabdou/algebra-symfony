<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Tests\Unit\DependencyInjection\Compiler;

use Nalabdou\Algebra\Adapter\AdapterRegistry;
use Nalabdou\Algebra\Symfony\DependencyInjection\Compiler\AdapterPass;
use Nalabdou\Algebra\Symfony\EventListener\AlgebraBootstrapListener;
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

        $this->container->register('algebra.adapter_registry', AdapterRegistry::class);

        $listener = new Definition(AlgebraBootstrapListener::class);
        $listener->setArgument('$adapters', []);
        $this->container->setDefinition('algebra.bootstrap_listener', $listener);
    }

    public function testWiresTaggedAdapterIntoRegistry(): void
    {
        $def = new Definition('App\Adapter\CsvAdapter');
        $def->addTag('algebra.adapter', ['priority' => 50]);
        $this->container->setDefinition('app.csv_adapter', $def);

        (new AdapterPass())->process($this->container);

        $calls = $this->container->getDefinition('algebra.adapter_registry')->getMethodCalls();
        self::assertCount(1, $calls);
        self::assertSame('register', $calls[0][0]);
        self::assertSame(50, $calls[0][1][1]);
    }

    public function testWiresTaggedAdapterIntoBootstrapListener(): void
    {
        $def = new Definition('App\Adapter\CsvAdapter');
        $def->addTag('algebra.adapter', ['priority' => 75]);
        $this->container->setDefinition('app.csv_adapter', $def);

        (new AdapterPass())->process($this->container);

        $args = $this->container->getDefinition('algebra.bootstrap_listener')->getArguments();
        $entries = $args['$adapters'];
        self::assertCount(1, $entries);
        self::assertSame(75, $entries[0]['priority']);
    }

    public function testWiresMultipleAdaptersIntoBoth(): void
    {
        foreach (['csv', 'redis', 'pdo'] as $name) {
            $def = new Definition("App\\Adapter\\{$name}Adapter");
            $def->addTag('algebra.adapter', ['priority' => 10]);
            $this->container->setDefinition("app.{$name}_adapter", $def);
        }

        (new AdapterPass())->process($this->container);

        $regCalls = $this->container->getDefinition('algebra.adapter_registry')->getMethodCalls();
        $listArgs = $this->container->getDefinition('algebra.bootstrap_listener')->getArguments()['$adapters'];

        self::assertCount(3, $regCalls);
        self::assertCount(3, $listArgs);
    }

    public function testUsesDefaultPriorityZeroWhenNotSet(): void
    {
        $def = new Definition('App\Adapter\SomeAdapter');
        $def->addTag('algebra.adapter');
        $this->container->setDefinition('app.some_adapter', $def);

        (new AdapterPass())->process($this->container);

        $calls = $this->container->getDefinition('algebra.adapter_registry')->getMethodCalls();
        self::assertSame(0, $calls[0][1][1]);
    }

    public function testNoTaggedServicesNoMethodCalls(): void
    {
        (new AdapterPass())->process($this->container);

        $calls = $this->container->getDefinition('algebra.adapter_registry')->getMethodCalls();
        self::assertCount(0, $calls);
    }

    public function testSkipsRegistryGracefullyWhenMissing(): void
    {
        $container = new ContainerBuilder();
        $listener = new Definition(AlgebraBootstrapListener::class);
        $listener->setArgument('$adapters', []);
        $container->setDefinition('algebra.bootstrap_listener', $listener);

        $def = new Definition('App\Adapter\CsvAdapter');
        $def->addTag('algebra.adapter', ['priority' => 50]);
        $container->setDefinition('app.csv_adapter', $def);

        (new AdapterPass())->process($container);
        self::assertTrue(true);
    }

    public function testSkipsListenerGracefullyWhenMissing(): void
    {
        $container = new ContainerBuilder();
        $container->register('algebra.adapter_registry', AdapterRegistry::class);

        $def = new Definition('App\Adapter\CsvAdapter');
        $def->addTag('algebra.adapter', ['priority' => 50]);
        $container->setDefinition('app.csv_adapter', $def);

        (new AdapterPass())->process($container);
        self::assertTrue(true);
    }
}
