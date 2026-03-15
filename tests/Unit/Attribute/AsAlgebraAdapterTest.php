<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Tests\Unit\Attribute;

use Nalabdou\Algebra\Symfony\Attribute\AsAlgebraAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AsAlgebraAdapter::class)]
final class AsAlgebraAdapterTest extends TestCase
{
    public function testDefaultPriorityIsZero(): void
    {
        $attr = new AsAlgebraAdapter();
        self::assertSame(0, $attr->priority);
    }

    public function testAcceptsCustomPriority(): void
    {
        $attr = new AsAlgebraAdapter(priority: 75);
        self::assertSame(75, $attr->priority);
    }

    public function testIsPhpAttribute(): void
    {
        $reflection = new \ReflectionClass(AsAlgebraAdapter::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        self::assertNotEmpty($attributes);
    }

    public function testTargetsClass(): void
    {
        $reflection = new \ReflectionClass(AsAlgebraAdapter::class);
        $attribute = $reflection->getAttributes(\Attribute::class)[0]->newInstance();

        self::assertSame(\Attribute::TARGET_CLASS, $attribute->flags);
    }

    public function testCanBeAppliedWithPriority(): void
    {
        $class = new #[AsAlgebraAdapter(priority: 50)] class {};

        $attrs = (new \ReflectionClass($class))->getAttributes(AsAlgebraAdapter::class);
        self::assertCount(1, $attrs);
        self::assertSame(50, $attrs[0]->newInstance()->priority);
    }
}
