<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Tests\Integration;

use Nalabdou\Algebra\Adapter\AdapterRegistry;
use Nalabdou\Algebra\Algebra;
use Nalabdou\Algebra\Contract\AdapterInterface;
use Nalabdou\Algebra\Contract\AggregateInterface;
use Nalabdou\Algebra\Symfony\AlgebraBundle;
use Nalabdou\Algebra\Symfony\DependencyInjection\AlgebraExtension;
use Nalabdou\Algebra\Symfony\DependencyInjection\Compiler\AdapterPass;
use Nalabdou\Algebra\Symfony\DependencyInjection\Compiler\AggregatePass;
use Nalabdou\Algebra\Symfony\EventListener\AlgebraBootstrapListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[CoversClass(AlgebraBundle::class)]
#[CoversClass(AlgebraExtension::class)]
#[CoversClass(AggregatePass::class)]
#[CoversClass(AdapterPass::class)]
#[CoversClass(AlgebraBootstrapListener::class)]
final class BundleIntegrationTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        Algebra::reset();

        $this->container = new ContainerBuilder();
        $bundle = new AlgebraBundle();
        $bundle->build($this->container);
        (new AlgebraExtension())->load([[]], $this->container);
    }

    private function makeMainRequestEvent(): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(\Symfony\Component\HttpKernel\KernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    public function testAllCoreServicesRegistered(): void
    {
        foreach (
            [
                'algebra.factory',
                'algebra.evaluator',
                'algebra.aggregates',
                'algebra.adapter_registry',
                'algebra.planner',
                'algebra.accessor',
                'algebra.cache',
                'algebra.bootstrap_listener',
            ] as $id
        ) {
            self::assertTrue($this->container->hasDefinition($id), "Missing: {$id}");
        }
    }

    public function testAdapterRegistryServiceIsPublic(): void
    {
        self::assertTrue($this->container->getDefinition('algebra.adapter_registry')->isPublic());
    }

    public function testAggregatePassWiresIntoRegistryAndListener(): void
    {
        $def = new Definition(AnonymousAggregate::class);
        $def->addTag('algebra.aggregate');
        $this->container->setDefinition('app.test_aggregate', $def);

        (new AggregatePass())->process($this->container);

        $registryCalls = $this->container->getDefinition('algebra.aggregates')->getMethodCalls();
        $listenerArgs = $this->container->getDefinition('algebra.bootstrap_listener')->getArguments();

        self::assertNotEmpty($registryCalls);
        self::assertNotEmpty($listenerArgs['$aggregates']);
    }

    public function testAdapterPassWiresIntoRegistryAndListener(): void
    {
        $def = new Definition(AnonymousAdapter::class);
        $def->addTag('algebra.adapter', ['priority' => 50]);
        $this->container->setDefinition('app.test_adapter', $def);

        (new AdapterPass())->process($this->container);

        $registryCalls = $this->container->getDefinition('algebra.adapter_registry')->getMethodCalls();
        $listenerArgs = $this->container->getDefinition('algebra.bootstrap_listener')->getArguments();

        self::assertNotEmpty($registryCalls, 'AdapterPass must wire into adapter_registry');
        self::assertNotEmpty($listenerArgs['$adapters'], 'AdapterPass must wire into bootstrap_listener');
    }

    public function testPipelineWorksStandalone(): void
    {
        $result = Algebra::from([['id' => 1, 'status' => 'paid'], ['id' => 2, 'status' => 'pending']])
            ->where("item['status'] == 'paid'")
            ->toArray();

        self::assertCount(1, $result);
    }

    public function testCustomAggregateAvailableViaListener(): void
    {
        $agg = new class implements AggregateInterface {
            public function name(): string
            {
                return 'triple_sum';
            }

            public function compute(array $values): mixed
            {
                return \array_sum($values) * 3;
            }
        };

        $listener = new AlgebraBootstrapListener(aggregates: [$agg]);
        $listener->onKernelRequest($this->makeMainRequestEvent());

        $result = Algebra::from([['v' => 10], ['v' => 20]])
            ->aggregate(['ts' => 'triple_sum(v)'])
            ->toArray();

        self::assertSame(90, $result[0]['ts']);
    }

    public function testCustomAdapterAvailableViaAlgebraFromAfterListener(): void
    {
        $adapter = new class implements AdapterInterface {
            public function supports(mixed $input): bool
            {
                return '__bundle_test__' === $input;
            }

            public function toArray(mixed $input): array
            {
                return [['source' => 'bundle']];
            }
        };

        $listener = new AlgebraBootstrapListener(
            adapters: [['adapter' => $adapter, 'priority' => 50]]
        );
        $listener->onKernelRequest($this->makeMainRequestEvent());

        $result = Algebra::from('__bundle_test__')->toArray();
        self::assertSame('bundle', $result[0]['source']);
    }

    public function testAdapterRegistryHas3BuiltinsByDefault(): void
    {
        $registry = new AdapterRegistry();
        self::assertSame(3, $registry->count());
    }
}

/** @internal test fixture */
final class AnonymousAggregate implements AggregateInterface
{
    public function name(): string
    {
        return 'anon_agg';
    }

    public function compute(array $values): mixed
    {
        return null;
    }
}

/** @internal test fixture */
final class AnonymousAdapter implements AdapterInterface
{
    public function supports(mixed $input): bool
    {
        return false;
    }

    public function toArray(mixed $input): array
    {
        return [];
    }
}
