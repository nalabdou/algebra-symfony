# algebra-symfony

Symfony 7 bundle for [algebra-php](https://github.com/nalabdou/algebra-php) — the pure PHP 8.2 relational algebra engine.

The bundle works entirely with the **public algebra-php API**. No modifications
to algebra-php are required or performed.

## What the bundle adds

| What | How |
|---|---|
| DI-managed services | Inject `CollectionFactory`, `ExpressionEvaluator`, `AggregateRegistry` |
| Custom aggregates | `#[AsAggregate]` attribute — one attribute, zero config |
| Custom adapters | `#[AsAlgebraAdapter(priority: N)]` — auto-injected into factory |
| Doctrine adapters | Auto-detected when `doctrine/orm` / `doctrine/collections` present |
| Bootstrap | `AlgebraBootstrapListener` re-registers tagged aggregates on first request |

## What is NOT in this bundle

- **Twig filters** — provided by `nalabdou/algebra-twig` [*Comming soon*] (separate package)
- **Modifications to algebra-php** — the bundle only uses its public API

## Contents

- [Installation](installation.md)
- [Configuration](configuration.md)
- [DI services](di-services.md)
- [Doctrine adapters](adapters.md)
- [Custom aggregates](custom-aggregates.md)
- [Custom adapters](custom-adapters.md)
