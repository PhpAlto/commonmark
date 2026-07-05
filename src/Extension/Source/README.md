# Source Extension

## Summary

The flagship of the set. `@source` directives embed a real file -- not a copy -- into your documentation at parse time, with automatic syntax class detection, precise line-range selection, optional line numbers, and per-line highlighting. Your docs and your source stay in sync by construction.

## Installation

### composer require

```bash
composer require alto/commonmark
```

### Registration

```php
use Alto\CommonMark\Extension\Source\SourceExtension;
use League\CommonMark\Environment\Environment;

$environment = new Environment();
$environment->addExtension(new SourceExtension(__DIR__));
```

## Usage

```markdown
@source "src/Service.php"
@source "src/Service.php" {lines: 10-30, numbers: true, highlight: "14,18-20"}
```

## Configuration

- Constructor: `new SourceExtension(string $basePath='.', array $allowedExtensions=[], bool $escapeHtml=true, int $maxFileSize=1048576)`.
- Supports extension allowlists and max-size protections.

## Minimal Example

```markdown
# Service excerpt

@source "src/Service.php" {lines: 1-25, numbers: true}
```

## Development

This extension is actively developed in the [`alto/commonmark`](https://github.com/PhpAlto/commonmark) monorepo.

- [Report a bug](https://github.com/PhpAlto/commonmark/issues/new?labels=bug)
- [Suggest a feature](https://github.com/PhpAlto/commonmark/issues/new?labels=enhancement)
- [Open a Pull Request](https://github.com/PhpAlto/commonmark/pulls)

## License

MIT License -- [Simon André](https://smnandre.dev) & [Alto](https://github.com/PhpAlto)
