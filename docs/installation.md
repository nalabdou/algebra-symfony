# Installation

## Requirements

| Dependency | Version |
|---|---|
| PHP | ≥ 8.2 |
| nalabdou/algebra-php | ^1.0 |
| symfony/framework-bundle | ^7.0 |

**Optional:**
- `doctrine/orm ^3.0` — enables Doctrine QueryBuilder adapter
- `doctrine/collections ^2.0` — enables Doctrine Collection adapter

## Install

```bash
composer require nalabdou/algebra-symfony
```

Symfony Flex auto-registers the bundle. Without Flex, add it manually:

```php
// config/bundles.php
return [
    // ...
    Nalabdou\Algebra\Symfony\AlgebraBundle::class => ['all' => true],
];
```

## Verify

```bash
php bin/console debug:container algebra.
```

Expected output:

```
algebra.factory     Nalabdou\Algebra\Collection\CollectionFactory
algebra.evaluator   Nalabdou\Algebra\Expression\ExpressionEvaluator
algebra.aggregates  Nalabdou\Algebra\Aggregate\AggregateRegistry
algebra.adapter_registry  Nalabdou\Algebra\Adapter\AdapterRegistry
```

## Next steps

- [Configuration](configuration.md) — strict mode
- [DI services](di-services.md) — inject CollectionFactory into your services
- [Custom aggregates](custom-aggregates.md) — register your own aggregate functions
- [Custom adapters](custom-adapters.md) — teach the factory to accept new input types
