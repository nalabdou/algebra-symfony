<?php

declare(strict_types=1);

namespace App\Adapter;

use Nalabdou\Algebra\Contract\AdapterInterface;
use Nalabdou\Algebra\Symfony\Attribute\AsAlgebraAdapter;

/**
 * Demo: custom adapter auto-registered via #[AsAlgebraAdapter].
 *
 * Priority 50 — checked after Doctrine adapters (100/90) but before
 * the built-in array/generator/traversable adapters.
 *
 * Usage:
 * ```php
 * $result = Algebra::from('/path/to/orders.csv')
 *     ->where("item['status'] == 'paid'")
 *     ->groupBy('region')
 *     ->aggregate(['revenue' => 'sum(amount)'])
 *     ->toArray();
 * ```
 */
#[AsAlgebraAdapter(priority: 50)]
final class CsvFileAdapter implements AdapterInterface
{
    public function supports(mixed $input): bool
    {
        return \is_string($input)
            && \str_ends_with($input, '.csv')
            && \file_exists($input);
    }

    public function toArray(mixed $input): array
    {
        $rows = [];
        $handle = \fopen($input, 'r');
        $header = \fgetcsv($handle);

        while (($row = \fgetcsv($handle)) !== false) {
            $rows[] = \array_combine($header, $row);
        }

        \fclose($handle);

        return $rows;
    }
}
