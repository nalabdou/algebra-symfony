<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Tests\Unit\EventListener;

use Nalabdou\Algebra\Algebra;
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

    public function testResetsAlgebraOnMainRequest(): void
    {
        $before = Algebra::aggregates();
        (new AlgebraBootstrapListener())->onKernelRequest($this->makeEvent());
        $after = Algebra::aggregates();

        self::assertNotSame($before, $after);
    }

    public function testIgnoresSubRequests(): void
    {
        $before = Algebra::aggregates();
        (new AlgebraBootstrapListener())->onKernelRequest($this->makeEvent(HttpKernelInterface::SUB_REQUEST));
        $after = Algebra::aggregates();

        self::assertSame($before, $after);
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
            $this->makeAggregate('agg_c'),
        ]);

        $listener->onKernelRequest($this->makeEvent());

        self::assertTrue(Algebra::aggregates()->has('agg_a'));
        self::assertTrue(Algebra::aggregates()->has('agg_b'));
        self::assertTrue(Algebra::aggregates()->has('agg_c'));
    }

    public function testOnlyBootsOnce(): void
    {
        $listener = new AlgebraBootstrapListener();
        $event = $this->makeEvent();

        $listener->onKernelRequest($event);
        $registryAfterFirst = Algebra::aggregates();

        $listener->onKernelRequest($event);
        $registryAfterSecond = Algebra::aggregates();

        self::assertSame($registryAfterFirst, $registryAfterSecond);
    }

    public function testAggregateAvailableInPipelineAfterBoot(): void
    {
        $listener = new AlgebraBootstrapListener(aggregates: [
            $this->makeAggregate('test_sum'),
        ]);
        $listener->onKernelRequest($this->makeEvent());

        $result = Algebra::from([['v' => 10], ['v' => 20]])
            ->aggregate(['total' => 'test_sum(v)'])
            ->toArray();

        self::assertSame(30, $result[0]['total']);
    }
}
