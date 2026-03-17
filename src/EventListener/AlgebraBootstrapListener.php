<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\EventListener;

use Nalabdou\Algebra\Algebra;
use Nalabdou\Algebra\Contract\AdapterInterface;
use Nalabdou\Algebra\Contract\AggregateInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Bootstraps algebra-php on the first request using only the public API.
 *
 * On the first main request this listener:
 *
 * 1. Calls `Algebra::reset()` — clears all lazy singletons.
 * 2. Re-registers DI-tagged **aggregates** into `Algebra::aggregates()`.
 * 3. Re-registers DI-tagged **adapters** into `Algebra::adapters()`.
 *
 * After boot, `Algebra::from()` accepts every custom input type registered
 * via `#[AsAlgebraAdapter]` — no need to inject `CollectionFactory` manually.
 *
 * ```php
 * // After #[AsAlgebraAdapter(priority: 50)] on CsvFileAdapter:
 * Algebra::from('/data/orders.csv')->where(...)->toArray(); // works
 *
 * // After #[AsAlgebraAdapter(priority: 100)] on DoctrineQueryBuilderAdapter:
 * Algebra::from($queryBuilder)->groupBy('region')->toArray(); // works
 * ```
 *
 * Runs once at priority 256 — before any controller or subscriber.
 */
final class AlgebraBootstrapListener
{
    private bool $booted = false;

    /**
     * @param AggregateInterface[] $aggregates custom aggregates (from AggregatePass)
     * @param AdapterInterface[]   $adapters   custom adapters with priorities (from AdapterPass)
     *                                         each entry: ['adapter' => AdapterInterface, 'priority' => int]
     */
    public function __construct(
        private readonly array $aggregates = [],
        private readonly array $adapters = [],
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->booted || !$event->isMainRequest()) {
            return;
        }

        Algebra::reset();

        foreach ($this->aggregates as $aggregate) {
            Algebra::aggregates()->register($aggregate);
        }

        foreach ($this->adapters as $entry) {
            Algebra::adapters()->register($entry['adapter'], $entry['priority']);
        }

        $this->booted = true;
    }
}
