# HeadingLevel Extension

## Summary

The one you don't need until you really do -- then it's irreplaceable. Transforms heading levels after parsing so that content written for one heading context can be safely embedded in another without hierarchy collisions. Supports fixed shifting, explicit level remapping, and a callback option that gives you complete control over every heading in the document.

## Installation

### composer require

```bash
composer require alto/commonmark
```

### Registration

```php
use Alto\CommonMark\Extension\HeadingLevel\HeadingLevelExtension;
use League\CommonMark\Environment\Environment;

$environment = new Environment();
$environment->addExtension(new HeadingLevelExtension(['down' => 1]));
```

## Usage

No custom markdown directive is required; the extension processes standard headings after parse.

## Configuration

- `['down' => 1]`: shifts heading level up numerically (`h1 -> h2`, etc.).
- `['map' => [1 => 2, 2 => 3]]`: explicit per-level mapping.
- `['callback' => fn(int $level): int => ...]`: custom transformation logic.

## Minimal Example

```markdown
# Original H1

## Original H2

```

## Development

This extension is actively developed in the [`alto/commonmark`](https://github.com/PhpAlto/commonmark) monorepo.

- [Report a bug](https://github.com/PhpAlto/commonmark/issues/new?labels=bug)
- [Suggest a feature](https://github.com/PhpAlto/commonmark/issues/new?labels=enhancement)
- [Open a Pull Request](https://github.com/PhpAlto/commonmark/pulls)

## License

MIT License -- [Simon André](https://smnandre.dev) & [Alto](https://github.com/PhpAlto)
