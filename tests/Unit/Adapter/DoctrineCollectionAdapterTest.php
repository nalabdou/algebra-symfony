<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Tests\Unit\Adapter;

use Nalabdou\Algebra\Symfony\Adapter\DoctrineCollectionAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DoctrineCollectionAdapter::class)]
final class DoctrineCollectionAdapterTest extends TestCase
{
    private DoctrineCollectionAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new DoctrineCollectionAdapter();
    }

    public function testSupportsDoctrineArrayCollection(): void
    {
        $col = new \Doctrine\Common\Collections\ArrayCollection([]);
        self::assertTrue($this->adapter->supports($col));
    }

    public function testDoesNotSupportPlainArray(): void
    {
        self::assertFalse($this->adapter->supports([]));
    }

    public function testDoesNotSupportTraversable(): void
    {
        self::assertFalse($this->adapter->supports(new \ArrayObject()));
    }

    public function testConvertsArrayCollectionToArray(): void
    {
        $col = new \Doctrine\Common\Collections\ArrayCollection([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]);
        $result = $this->adapter->toArray($col);

        self::assertCount(2, $result);
        self::assertSame(1, $result[0]['id']);
    }

    public function testResultIsZeroIndexed(): void
    {
        $col = new \Doctrine\Common\Collections\ArrayCollection([5 => ['id' => 5]]);
        $result = $this->adapter->toArray($col);

        self::assertArrayHasKey(0, $result);
        self::assertArrayNotHasKey(5, $result);
    }

    public function testEmptyCollectionReturnsEmptyArray(): void
    {
        $col = new \Doctrine\Common\Collections\ArrayCollection([]);
        self::assertSame([], $this->adapter->toArray($col));
    }
}
