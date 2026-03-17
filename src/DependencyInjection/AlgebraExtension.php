<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Symfony\DependencyInjection;

use Nalabdou\Algebra\Adapter\AdapterRegistry;
use Nalabdou\Algebra\Aggregate\AggregateRegistry;
use Nalabdou\Algebra\Collection\CollectionFactory;
use Nalabdou\Algebra\Expression\ExpressionCache;
use Nalabdou\Algebra\Expression\ExpressionEvaluator;
use Nalabdou\Algebra\Expression\PropertyAccessor;
use Nalabdou\Algebra\Planner\QueryPlanner;
use Nalabdou\Algebra\Symfony\Adapter\DoctrineCollectionAdapter;
use Nalabdou\Algebra\Symfony\Adapter\DoctrineQueryBuilderAdapter;
use Nalabdou\Algebra\Symfony\Attribute\AsAggregate;
use Nalabdou\Algebra\Symfony\Attribute\AsAlgebraAdapter;
use Nalabdou\Algebra\Symfony\EventListener\AlgebraBootstrapListener;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Registers all algebra-php services in the Symfony DI container.
 *
 * Works entirely with the public algebra-php API — no modifications to
 * the algebra-php source are required.
 *
 * Services exposed:
 * - `algebra.factory`    — CollectionFactory (public, injectable)
 * - `algebra.evaluator`  — ExpressionEvaluator (public, injectable)
 * - `algebra.aggregates` — AggregateRegistry (public, injectable)
 *
 * Twig support lives in the separate nalabdou/algebra-twig package.
 */
final class AlgebraExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerCore($container, $config);
        $this->registerDoctrineAdapters($container);
        $this->registerBootstrapListener($container, $config);
        $this->configureAutoconfigure($container);
    }

    private function registerCore(ContainerBuilder $container, array $config): void
    {
        $container->register('algebra.accessor', PropertyAccessor::class)
            ->setPublic(false);

        $container->register('algebra.cache', ExpressionCache::class)
            ->setPublic(false);

        $container->register('algebra.evaluator', ExpressionEvaluator::class)
            ->setArgument('$propertyAccessor', new Reference('algebra.accessor'))
            ->setArgument('$cache', new Reference('algebra.cache'))
            ->setArgument('$strictMode', $config['strict_mode'])
            ->setPublic(true);

        $container->register('algebra.aggregates', AggregateRegistry::class)
            ->setPublic(true);

        $container->register('algebra.planner', QueryPlanner::class)
            ->setArgument(0, new Reference('algebra.evaluator'))
            ->setPublic(false);

        // CollectionFactory is available for injection as algebra.factory.
        // Built-in adapters are pre-registered in the AdapterRegistry.
        // Tagged adapters are injected into algebra.adapter_registry via AdapterPass.
        $container->register('algebra.adapter_registry', AdapterRegistry::class)
            ->setPublic(true);

        $container->register('algebra.factory', CollectionFactory::class)
            ->setArgument('$planner', new Reference('algebra.planner'))
            ->setArgument('$evaluator', new Reference('algebra.evaluator'))
            ->setArgument('$accessor', new Reference('algebra.accessor'))
            ->setArgument('$aggregates', new Reference('algebra.aggregates'))
            ->setArgument('$adapterRegistry', new Reference('algebra.adapter_registry'))
            ->setPublic(true);
    }

    private function registerBootstrapListener(ContainerBuilder $container, array $config): void
    {
        // The listener calls Algebra::reset() then re-registers tagged aggregates
        // using only the public algebra-php API (no modifications to algebra-php required).
        // Tagged adapters and aggregates are injected as arrays by compiler passes.
        $container->register('algebra.bootstrap_listener', AlgebraBootstrapListener::class)
            ->setArgument('$aggregates', [])   // filled by AggregatePass
            ->setArgument('$adapters', [])   // filled by AdapterPass
            ->addTag('kernel.event_listener', [
                'event' => 'kernel.request',
                'method' => 'onKernelRequest',
                'priority' => 256,
            ])
            ->setPublic(false);
    }

    private function registerDoctrineAdapters(ContainerBuilder $container): void
    {
        if (\class_exists(\Doctrine\ORM\QueryBuilder::class)) {
            $container->register('algebra.adapter.doctrine_query_builder', DoctrineQueryBuilderAdapter::class)
                ->addTag('algebra.adapter', ['priority' => 100])
                ->setPublic(false);
        }

        if (\class_exists(\Doctrine\Common\Collections\Collection::class)) {
            $container->register('algebra.adapter.doctrine_collection', DoctrineCollectionAdapter::class)
                ->addTag('algebra.adapter', ['priority' => 90])
                ->setPublic(false);
        }
    }

    private function configureAutoconfigure(ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            AsAggregate::class,
            static function (Definition $definition, AsAggregate $attribute): void {
                $definition->addTag('algebra.aggregate');
            }
        );

        $container->registerAttributeForAutoconfiguration(
            AsAlgebraAdapter::class,
            static function (Definition $definition, AsAlgebraAdapter $attribute): void {
                $definition->addTag('algebra.adapter', ['priority' => $attribute->priority]);
            }
        );
    }

    public function getAlias(): string
    {
        return 'algebra';
    }
}
