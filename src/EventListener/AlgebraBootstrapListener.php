<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\EventListener;

use Nalabdou\Algebra\Algebra;
use Nalabdou\Algebra\Contract\AggregateInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Bootstraps algebra-php on the first request using only the public API.
 *
 * ### What this listener does
 *
 * 1. Calls `Algebra::reset()` — clears all lazy singletons.
 * 2. Re-registers all DI-tagged aggregates into the fresh `Algebra::aggregates()`.
 *
 * After this, `Algebra::from()` works normally and custom aggregates registered
 * via `#[AsAggregate]` or the `algebra.aggregate` tag are available.
 *
 * ### Custom adapters
 *
 * Doctrine adapters and custom adapters tagged with `#[AsAlgebraAdapter]` are
 * available via the injectable `algebra.factory` service (CollectionFactory).
 * They are **not** wired into `Algebra::from()` because the Algebra static
 * factory is private and the bundle does not modify algebra-php source code.
 *
 * To use custom adapters with `Algebra::from()`, register them in your app:
 *
 * ```php
 * // In a Symfony kernel.request listener (priority < 256):
 * use Nalabdou\Algebra\Algebra;
 * use App\Adapter\CsvFileAdapter;
 *
 * Algebra::factory();  // ensure singleton exists
 * // Then use algebra.factory service for adapter-aware pipelines
 * ```
 *
 * Or simply inject `CollectionFactory` instead of using `Algebra::from()`.
 *
 * Runs once at priority 256 — before any controller or subscriber.
 */
final class AlgebraBootstrapListener
{
    private bool $booted = false;

    /**
     * @param AggregateInterface[] $aggregates custom aggregates from DI (AggregatePass)
     */
    public function __construct(
        private readonly array $aggregates = [],
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->booted || !$event->isMainRequest()) {
            return;
        }

        // Reset clears all lazy singletons — they rebuild on next access
        Algebra::reset();

        // Re-register all DI-tagged aggregates into the fresh registry
        foreach ($this->aggregates as $aggregate) {
            Algebra::aggregates()->register($aggregate);
        }

        $this->booted = true;
    }
}
