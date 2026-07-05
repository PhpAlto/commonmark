# Import Extension

## Summary

Solves the copy-paste drift problem between documentation and source code. `@import` directives pull external file content directly into fenced code blocks at parse time -- with optional line-range selection, language hinting, and indentation control. Your code samples stay in sync with the actual source by definition, not by discipline.

## Installation

### composer require

```bash
composer require alto/commonmark
```

### Registration

```php
use Alto\CommonMark\Extension\Import\ImportExtension;
use League\CommonMark\Environment\Environment;

$environment = new Environment();
$environment->addExtension(new ImportExtension(__DIR__ . '/docs'));
```

## Usage

```markdown
@import "path/to/file.md"
@import "src/Handler.php" {lines: 10-40, lang: php, indent: 2}
```

## Configuration

- Constructor: `new ImportExtension(string $basePath = '.', int $maxDepth = 10)`.
- Guards against circular imports and excessive nesting depth.

## Minimal Example

```markdown
# API

@import "snippets/auth.md"
@import "src/Auth.php" {lines: 1-30, lang: php}
```

## Development

This extension is actively developed in the [`alto/commonmark`](https://github.com/PhpAlto/commonmark) monorepo.

- [Report a bug](https://github.com/PhpAlto/commonmark/issues/new?labels=bug)
- [Suggest a feature](https://github.com/PhpAlto/commonmark/issues/new?labels=enhancement)
- [Open a Pull Request](https://github.com/PhpAlto/commonmark/pulls)

## License

MIT License -- [Simon André](https://smnandre.dev) & [Alto](https://github.com/PhpAlto)
