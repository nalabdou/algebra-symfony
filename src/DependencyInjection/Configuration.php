<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Bundle configuration schema.
 *
 * Full reference:
 *
 * ```yaml
 * # config/packages/algebra.yaml
 * algebra:
 *     strict_mode: true    # throw on invalid expressions (default: true)
 * ```
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('algebra');
        $root = $tree->getRootNode();

        $root
            ->children()
                ->booleanNode('strict_mode')
                    ->defaultTrue()
                    ->info(
                        'When true, invalid expressions throw RuntimeException. '.
                        'Set false for lenient mode — useful when expressions come from user input.'
                    )
                ->end()
            ->end();

        return $tree;
    }
}
