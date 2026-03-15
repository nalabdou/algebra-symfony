<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony;

use Nalabdou\Algebra\Contract\AdapterInterface;
use Nalabdou\Algebra\Contract\AggregateInterface;
use Nalabdou\Algebra\Symfony\Attribute\AsAggregate;
use Nalabdou\Algebra\Symfony\Attribute\AsAlgebraAdapter;
use Nalabdou\Algebra\Symfony\DependencyInjection\AlgebraExtension;
use Nalabdou\Algebra\Symfony\DependencyInjection\Compiler\AdapterPass;
use Nalabdou\Algebra\Symfony\DependencyInjection\Compiler\AggregatePass;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * AlgebraBundle — Symfony 7 integration for algebra-php.
 *
 * ### Registration (Flex does this automatically)
 * ```php
 * // config/bundles.php
 * return [
 *     Nalabdou\Algebra\Symfony\AlgebraBundle::class => ['all' => true],
 * ];
 * ```
 *
 * ### What this bundle provides
 * - **DI services** — factory, evaluator, cache, accessor, aggregates, planner
 * - **Auto-configuration** — any `AggregateInterface` or `AdapterInterface`
 *   implementation is tagged and registered automatically
 * - **PHP attributes** — `#[AsAggregate]` and `#[AsAlgebraAdapter(priority: n)]`
 * - **Doctrine adapters** — auto-detected when doctrine/orm or doctrine/collections is present
 *
 * ### Auto-configuration
 * Services implementing `AggregateInterface` are tagged `algebra.aggregate`.
 * Services implementing `AdapterInterface` are tagged `algebra.adapter`.
 * This works with both PHP attributes and services.yaml declarations.
 */
final class AlgebraBundle extends Bundle
{
    public function getContainerExtension(): AlgebraExtension
    {
        return new AlgebraExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Auto-tag AggregateInterface implementations
        $container->registerForAutoconfiguration(AggregateInterface::class)
            ->addTag('algebra.aggregate');

        // Auto-tag AdapterInterface implementations
        $container->registerForAutoconfiguration(AdapterInterface::class)
            ->addTag('algebra.adapter');

        // Wire #[AsAggregate] attribute → algebra.aggregate tag
        $container->registerAttributeForAutoconfiguration(
            AsAggregate::class,
            static function (ChildDefinition $definition, AsAggregate $attribute): void {
                $definition->addTag('algebra.aggregate');
            }
        );

        // Wire #[AsAlgebraAdapter] attribute → algebra.adapter tag with priority
        $container->registerAttributeForAutoconfiguration(
            AsAlgebraAdapter::class,
            static function (ChildDefinition $definition, AsAlgebraAdapter $attribute): void {
                $definition->addTag('algebra.adapter', ['priority' => $attribute->priority]);
            }
        );

        $container->addCompilerPass(new AggregatePass());
        $container->addCompilerPass(new AdapterPass());
    }
}
