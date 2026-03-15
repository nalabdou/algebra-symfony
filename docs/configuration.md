# Configuration

The bundle works with an empty config. All options have sensible defaults.

## Reference

```yaml
# config/packages/algebra.yaml
algebra:
    strict_mode: true    # default: true
```

## `strict_mode`

Controls how the expression evaluator handles invalid string expressions.

**`true` (default)** — throws `\RuntimeException` with a detailed message
including the expression and the parse error:

```
algebra-php expression error.
Expression : @@@invalid@@@
Reason     : Unexpected character '@' at offset 0
```

**`false`** — returns `false` (for `evaluate()`) or `null` (for `resolve()`)
silently. Use this when expressions come from user input and you prefer
graceful degradation over exceptions.

```yaml
# config/packages/algebra.yaml
algebra:
    strict_mode: false
```

## Environment-specific config

```yaml
# config/packages/dev/algebra.yaml
algebra:
    strict_mode: true

# config/packages/prod/algebra.yaml
algebra:
    strict_mode: true
```
