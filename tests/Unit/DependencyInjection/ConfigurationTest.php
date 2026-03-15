<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\Tests\Unit\DependencyInjection;

use Nalabdou\Algebra\Symfony\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

#[CoversClass(Configuration::class)]
final class ConfigurationTest extends TestCase
{
    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }

    public function testStrictModeDefaultsToTrue(): void
    {
        $config = $this->process([]);
        self::assertTrue($config['strict_mode']);
    }

    public function testStrictModeAcceptsFalse(): void
    {
        $config = $this->process(['strict_mode' => false]);
        self::assertFalse($config['strict_mode']);
    }

    public function testEmptyConfigIsValid(): void
    {
        $config = $this->process([]);
        self::assertArrayHasKey('strict_mode', $config);
    }

    public function testConfigurationTreeBuilderRootNode(): void
    {
        $tree = (new Configuration())->getConfigTreeBuilder();
        self::assertSame('algebra', $tree->buildTree()->getName());
    }
}
