<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Tests\Unit\EventListener;

use Nalabdou\Algebra\Algebra;
use Nalabdou\Algebra\Contract\AdapterInterface;
use Nalabdou\Algebra\Contract\AggregateInterface;
use Nalabdou\Algebra\Symfony\EventListener\AlgebraBootstrapListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[CoversClass(AlgebraBootstrapListener::class)]
final class AlgebraBootstrapListenerTest extends TestCase
{
    protected function setUp(): void
    {
        Algebra::reset();
    }

    private function makeEvent(int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(KernelInterface::class),
            Request::create('/'),
            $type
        );
    }

    private function makeAggregate(string $name): AggregateInterface
    {
        return new class($name) implements AggregateInterface {
            public function __construct(private readonly string $n)
            {
            }

            public function name(): string
            {
                return $this->n;
            }

            public function compute(array $values): mixed
            {
                return \array_sum($values);
            }
        };
    }

    private function makeAdapter(string $key): AdapterInterface
    {
        return new class($key) implements AdapterInterface {
            public function __construct(private readonly string $k)
            {
            }

            public function supports(mixed $input): bool
            {
                return $input === $this->k;
            }

            public function toArray(mixed $input): array
            {
                return [['key' => $this->k]];
            }
        };
    }

    public function testResetsAlgebraOnMainRequest(): void
    {
        $before = Algebra::aggregates();
        (new AlgebraBootstrapListener())->onKernelRequest($this->makeEvent());
        self::assertNotSame($before, Algebra::aggregates());
    }

    public function testIgnoresSubRequests(): void
    {
        $before = Algebra::aggregates();
        (new AlgebraBootstrapListener())->onKernelRequest($this->makeEvent(HttpKernelInterface::SUB_REQUEST));
        self::assertSame($before, Algebra::aggregates());
    }

    public function testRegistersAggregatesAfterReset(): void
    {
        $agg = $this->makeAggregate('my_sum');
        $listener = new AlgebraBootstrapListener(aggregates: [$agg]);

        $listener->onKernelRequest($this->makeEvent());

        self::assertTrue(Algebra::aggregates()->has('my_sum'));
    }

    public function testRegistersMultipleAggregates(): void
    {
        $listener = new AlgebraBootstrapListener(aggregates: [
            $this->makeAggregate('agg_a'),
            $this->makeAggregate('agg_b'),
        ]);

        $listener->onKernelRequest($this->makeEvent());

        self::assertTrue(Algebra::aggregates()->has('agg_a'));
        self::assertTrue(Algebra::aggregates()->has('agg_b'));
    }

    public function testRegistersAdaptersAfterReset(): void
    {
        $adapter = $this->makeAdapter('__csv__');
        $listener = new AlgebraBootstrapListener(
            adapters: [['adapter' => $adapter, 'priority' => 50]]
        );

        $listener->onKernelRequest($this->makeEvent());

        $found = Algebra::adapters()->find('__csv__');
        self::assertSame($adapter, $found);
    }

    public function testRegistersMultipleAdaptersWithPriorities(): void
    {
        $a1 = $this->makeAdapter('__src1__');
        $a2 = $this->makeAdapter('__src2__');

        $listener = new AlgebraBootstrapListener(adapters: [
            ['adapter' => $a1, 'priority' => 100],
            ['adapter' => $a2, 'priority' => 50],
        ]);

        $listener->onKernelRequest($this->makeEvent());

        self::assertSame($a1, Algebra::adapters()->find('__src1__'));
        self::assertSame($a2, Algebra::adapters()->find('__src2__'));
    }

    public function testAdapterRegisteredViaListenerWorksInAlgebraFrom(): void
    {
        $adapter = $this->makeAdapter('__my_source__');
        $listener = new AlgebraBootstrapListener(
            adapters: [['adapter' => $adapter, 'priority' => 50]]
        );

        $listener->onKernelRequest($this->makeEvent());

        $result = Algebra::from('__my_source__')->toArray();
        self::assertSame('__my_source__', $result[0]['key']);
    }

    public function testAggregateAvailableInPipelineAfterBoot(): void
    {
        $listener = new AlgebraBootstrapListener(aggregates: [$this->makeAggregate('test_sum')]);
        $listener->onKernelRequest($this->makeEvent());

        $result = Algebra::from([['v' => 10], ['v' => 20]])
            ->aggregate(['total' => 'test_sum(v)'])
            ->toArray();

        self::assertSame(30, $result[0]['total']);
    }

    public function testOnlyBootsOnce(): void
    {
        $listener = new AlgebraBootstrapListener();
        $event = $this->makeEvent();

        $listener->onKernelRequest($event);
        $registryAfterFirst = Algebra::aggregates();

        $listener->onKernelRequest($event);
        self::assertSame($registryAfterFirst, Algebra::aggregates());
    }
}
