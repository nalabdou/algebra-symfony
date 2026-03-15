<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Attribute;

/**
 * Marks a class as an algebra-php adapter and auto-injects it into the
 * {@see \Nalabdou\Algebra\Collection\CollectionFactory}.
 *
 * Apply to any class implementing {@see \Nalabdou\Algebra\Contract\AdapterInterface}.
 * The DI compiler pass ({@see \Nalabdou\Algebra\Symfony\DependencyInjection\Compiler\AdapterPass})
 * collects all tagged services, sorts by `priority` (highest first), and passes
 * them to `CollectionFactory::$adapters`.
 *
 * ### Priority guidelines
 * - 100+ : framework adapters (Doctrine QB, Eloquent)
 * - 50–99 : third-party adapters (CSV, Redis, API)
 * - 1–49  : application-specific adapters
 * - 0     : default (checked last, before built-in array/generator/traversable)
 *
 * ### Usage
 * ```php
 * use Nalabdou\Algebra\Contract\AdapterInterface;
 * use Nalabdou\Algebra\Symfony\Attribute\AsAlgebraAdapter;
 *
 * #[AsAlgebraAdapter(priority: 50)]
 * final class CsvFileAdapter implements AdapterInterface
 * {
 *     public function supports(mixed $input): bool
 *     {
 *         return is_string($input) && str_ends_with($input, '.csv') && file_exists($input);
 *     }
 *
 *     public function toArray(mixed $input): array
 *     {
 *         // read CSV and return rows
 *     }
 * }
 * ```
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsAlgebraAdapter
{
    public function __construct(
        public readonly int $priority = 0,
    ) {
    }
}
