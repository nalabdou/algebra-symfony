# Contributing

Contributions are welcome! This document covers how to get started, the
development workflow, and contribution guidelines.

---

## Getting started

```bash
git clone https://github.com/nalabdou/algebra-symfony
cd algebra-symfony
composer install
```

**Requirements:** PHP 8.2+, Composer.

---

## Running tests

```bash
make test             # all suites
make unit             # unit tests only
make integration      # integration tests only
make coverage         # HTML coverage report (requires Xdebug)
```

Or directly with PHPUnit:

```bash
vendor/bin/phpunit
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --filter LexerTest
```

---

## Code quality

```bash
make stan    # PHPStan level 5
make cs      # PHP-CS-Fixer dry run
make cs-fix  # auto-fix code style
make ci      # cs + stan + test (full CI)
```

---

## Coding standards

- PHP 8.2+ features encouraged: `readonly` properties, named arguments, first-class callables
- `declare(strict_types=1)` in every file
- `final` classes everywhere (no accidental inheritance)
- Full PHPDoc on every public method and class
- PHPStan level 5 — no suppression comments without justification

---

## Commit message format

```
type(scope): short description

Longer description if needed.
```

Types: `feat`, `fix`, `docs`, `test`, `refactor`, `perf`, `chore`

Examples:
```
feat(operation): add ShuffleOperation
fix(lexer): handle escaped quotes in double-quoted strings
docs(examples): add financial reporting guide
test(expression): add ParserTest for ternary precedence
perf(planner): improve CollapseConsecutiveMaps for long chains
```

---

## Submitting a pull request

1. Fork the repository
2. Create a feature branch: `git checkout -b feat/shuffle-operation`
3. Make your changes with tests
4. Run `make ci` to verify everything passes
5. Open a PR with a clear description of what and why

---

## Issues

Report bugs and request features at:
https://github.com/nalabdou/algebra-symfony/issues

Please include:
- PHP version and `composer show | grep algebra-symfony`
- Minimal reproducible example
- Expected vs actual output
