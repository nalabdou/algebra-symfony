<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Tests\Unit\Adapter;

use Doctrine\ORM\QueryBuilder;
use Nalabdou\Algebra\Symfony\Adapter\DoctrineQueryBuilderAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DoctrineQueryBuilderAdapter::class)]
final class DoctrineQueryBuilderAdapterTest extends TestCase
{
    private DoctrineQueryBuilderAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new DoctrineQueryBuilderAdapter();
    }

    public function testSupportsQueryBuilder(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        self::assertTrue($this->adapter->supports($qb));
    }

    public function testDoesNotSupportPlainArray(): void
    {
        self::assertFalse($this->adapter->supports([]));
    }

    public function testDoesNotSupportString(): void
    {
        self::assertFalse($this->adapter->supports('SELECT * FROM orders'));
    }

    public function testToArrayCallsGetArrayResult(): void
    {
        $rows = [['id' => 1], ['id' => 2]];
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects(self::once())->method('getArrayResult')->willReturn($rows);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::once())->method('getQuery')->willReturn($query);

        $result = $this->adapter->toArray($qb);
        self::assertSame($rows, $result);
    }
}
