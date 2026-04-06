# ContentSlicer Extension

## Summary

Genuinely rare in the CommonMark ecosystem. Most processors stop at rendering headings as `<h1>`…`<h6>` tags — this one goes further and restructures the document into a properly nested `<section>` tree that mirrors the heading hierarchy. The result is semantic HTML you can actually target with CSS, query with JavaScript, and traverse with accessibility tooling. No custom syntax required.

## Installation

### composer require

```bash
composer require alto/commonmark
```

### Registration

```php
use Alto\CommonMark\Extension\ContentSlicer\ContentSlicerExtension;
use League\CommonMark\Environment\Environment;

$environment = new Environment();
$environment->addExtension(new ContentSlicerExtension());
```

## Usage

No custom markdown directive is required; the extension processes standard headings after parse.

## Configuration

- Constructor: `new ContentSlicerExtension(int $minSectionLevel = 1)`.
- `0` wraps from `h1`, `1` wraps from `h2`, `2` wraps from `h3`, etc.

## Minimal Example

```markdown
# Title
Intro

## Section

Body
### Subsection

Details
```

## Development

This extension is actively developed in the [`alto/commonmark`](https://github.com/PhpAlto/commonmark) monorepo.

- [Report a bug](https://github.com/PhpAlto/commonmark/issues/new?labels=bug)
- [Suggest a feature](https://github.com/PhpAlto/commonmark/issues/new?labels=enhancement)
- [Open a Pull Request](https://github.com/PhpAlto/commonmark/pulls)

## License

MIT License — [Simon André](https://smnandre.dev) & [Alto](https://github.com/PhpAlto)
