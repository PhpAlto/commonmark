# TableOfContents Extension

## Summary

A must-have for any document longer than a page. Place `@toc` where you want the contents list and the extension takes care of the rest: headings are collected, anchor IDs are assigned, and a navigable nested list is rendered -- with filtering by level and configurable output style.

## Installation

### composer require

```bash
composer require alto/commonmark
```

### Registration

```php
use Alto\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\Environment\Environment;

$environment = new Environment();
$environment->addExtension(new TableOfContentsExtension());
```

## Usage

```markdown
@toc
@toc {min: 2, max: 3, title: "Contents", style: ordered}
```

## Configuration

- `min_level` / `max_level`: heading level bounds.
- `style`: `bullet` or `ordered`.
- `title`, `class`, `id`, `marker`: output/customization options.

## Minimal Example

```markdown
# Guide
@toc {min: 2}

## Intro

## Setup

### Advanced Setup

```

## Development

This extension is actively developed in the [`alto/commonmark`](https://github.com/PhpAlto/commonmark) monorepo.

- [Report a bug](https://github.com/PhpAlto/commonmark/issues/new?labels=bug)
- [Suggest a feature](https://github.com/PhpAlto/commonmark/issues/new?labels=enhancement)
- [Open a Pull Request](https://github.com/PhpAlto/commonmark/pulls)

## License

MIT License -- [Simon André](https://smnandre.dev) & [Alto](https://github.com/PhpAlto)
