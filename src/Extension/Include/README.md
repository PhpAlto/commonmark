# Include Extension

## Summary

The backbone of large documentation sets. `@include` directives pull in external markdown fragments and fully parse them inline — making one-file-per-section composition possible without a build system. Supports optional line-range selection for embedding partial files, with circular-include protection to keep deeply nested setups safe.

## Installation

### composer require

```bash
composer require alto/commonmark
```

### Registration

```php
use Alto\CommonMark\Extension\Include\IncludeExtension;
use League\CommonMark\Environment\Environment;

$environment = new Environment();
$environment->addExtension(new IncludeExtension(__DIR__ . '/docs'));
```

## Usage

```markdown
@include "sections/intro.md"
@include "sections/features.md" {lines: 5-20}
```

## Configuration

- Constructor: `new IncludeExtension(string $basePath='.', int $maxDepth=10, array $allowedExtensions=['md','markdown'], int $maxFileSize=1048576)`.
- Validates extension, file size, and readable path constraints.

## Minimal Example

```markdown
# Guide

@include "parts/overview.md"
@include "parts/setup.md"
```

## Development

This extension is actively developed in the [`alto/commonmark`](https://github.com/PhpAlto/commonmark) monorepo.

- [Report a bug](https://github.com/PhpAlto/commonmark/issues/new?labels=bug)
- [Suggest a feature](https://github.com/PhpAlto/commonmark/issues/new?labels=enhancement)
- [Open a Pull Request](https://github.com/PhpAlto/commonmark/pulls)

## License

MIT License — [Simon André](https://smnandre.dev) & [Alto](https://github.com/PhpAlto)
