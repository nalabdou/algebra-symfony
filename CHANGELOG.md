# Changelog

All notable changes to `nalabdou/algebra-symfony` are documented here.

## [1.0.0] — Initial release

### Added
- `AlgebraBundle` — Symfony 7 bundle entry point with compiler pass registration
- `AlgebraExtension` — DI container registration for all algebra-php services
- `Configuration` — `strict_mode` configuration key
- `AlgebraBootstrapListener` — resets Algebra singletons on first request and re-registers tagged aggregates
- `AggregatePass` — auto-registers `algebra.aggregate`-tagged services into `AggregateRegistry`
- `AdapterPass` — auto-injects `algebra.adapter`-tagged services (by priority) into `CollectionFactory`
- `AsAggregate` — PHP attribute for zero-config aggregate auto-registration
- `AsAlgebraAdapter` — PHP attribute for zero-config adapter auto-registration with priority
- `DoctrineCollectionAdapter` — auto-registered when `doctrine/collections` is present
- `DoctrineQueryBuilderAdapter` — auto-registered when `doctrine/orm` is present
- `registerAttributeForAutoconfiguration` support for both bundle attributes
