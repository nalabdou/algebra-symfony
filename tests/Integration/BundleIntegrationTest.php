<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Tests\Integration;

use Nalabdou\Algebra\Algebra;
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

    public function testAllCoreServicesRegistered(): void
    {
        $required = [
            'algebra.factory',
            'algebra.evaluator',
            'algebra.aggregates',
            'algebra.planner',
            'algebra.accessor',
            'algebra.cache',
            'algebra.bootstrap_listener',
        ];

        foreach ($required as $id) {
            self::assertTrue($this->container->hasDefinition($id), "Missing: {$id}");
        }
    }

    public function testAggregatePassWiresTaggedServiceIntoRegistry(): void
    {
        $def = new Definition(AnonymousAggregate::class);
        $def->addTag('algebra.aggregate');
        $this->container->setDefinition('app.test_aggregate', $def);

        (new AggregatePass())->process($this->container);

        $calls = $this->container->getDefinition('algebra.aggregates')->getMethodCalls();
        self::assertNotEmpty($calls);
        self::assertSame('register', $calls[0][0]);
    }

    public function testAggregatePassAlsoWiresIntoBootstrapListener(): void
    {
        $def = new Definition(AnonymousAggregate::class);
        $def->addTag('algebra.aggregate');
        $this->container->setDefinition('app.test_aggregate', $def);

        (new AggregatePass())->process($this->container);

        $listenerArgs = $this->container->getDefinition('algebra.bootstrap_listener')->getArguments();
        self::assertNotEmpty($listenerArgs['$aggregates']);
    }

    public function testAdapterPassWiresTaggedAdapterIntoFactory(): void
    {
        $def = new Definition(AnonymousAdapter::class);
        $def->addTag('algebra.adapter', ['priority' => 50]);
        $this->container->setDefinition('app.test_adapter', $def);

        (new AdapterPass())->process($this->container);

        $factoryArgs = $this->container->getDefinition('algebra.factory')->getArguments();
        self::assertNotEmpty($factoryArgs['$adapters']);
    }

    public function testPipelineWorksWithoutBundleAfterReset(): void
    {
        // Sanity check: algebra-php works standalone without the bundle
        $result = Algebra::from([['id' => 1, 'status' => 'paid'], ['id' => 2, 'status' => 'pending']])
            ->where("item['status'] == 'paid'")
            ->toArray();

        self::assertCount(1, $result);
    }

    public function testCustomAggregateViaListenerAvailableInPipeline(): void
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

    private function makeMainRequestEvent(): \Symfony\Component\HttpKernel\Event\RequestEvent
    {
        return new \Symfony\Component\HttpKernel\Event\RequestEvent(
            $this->createMock(\Symfony\Component\HttpKernel\KernelInterface::class),
            \Symfony\Component\HttpFoundation\Request::create('/'),
            \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST
        );
    }
}

/** @internal test fixture */
final class AnonymousAggregate implements AggregateInterface
{
    public function name(): string
    {
        return 'anon';
    }

    public function compute(array $values): mixed
    {
        return null;
    }
}

/** @internal test fixture */
final class AnonymousAdapter implements \Nalabdou\Algebra\Contract\AdapterInterface
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
