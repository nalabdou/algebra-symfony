<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Tests\Unit\Attribute;

use Nalabdou\Algebra\Symfony\Attribute\AsAggregate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AsAggregate::class)]
final class AsAggregateTest extends TestCase
{
    public function testIsPhpAttribute(): void
    {
        $reflection = new \ReflectionClass(AsAggregate::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        self::assertNotEmpty($attributes);
    }

    public function testTargetsClass(): void
    {
        $reflection = new \ReflectionClass(AsAggregate::class);
        $attribute = $reflection->getAttributes(\Attribute::class)[0]->newInstance();

        self::assertSame(\Attribute::TARGET_CLASS, $attribute->flags);
    }

    public function testCanBeAppliedToClass(): void
    {
        $class = new #[AsAggregate] class {};

        $attrs = (new \ReflectionClass($class))->getAttributes(AsAggregate::class);
        self::assertCount(1, $attrs);
    }
}
