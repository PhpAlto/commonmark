# LinkRewriter Extension

## Summary

Indispensable plumbing for any hosted documentation setup. Rewrites link and image URLs after parsing so your markdown stays decoupled from your deployment URL. Rules are composable and applied in sequence: prepend a base URI, swap URLs by exact match, replace with a regex pattern, or run a custom callback. Chain as many as you need.

## Installation

### composer require

```bash
composer require alto/commonmark
```

### Registration

```php
use Alto\CommonMark\Extension\LinkRewriter\LinkRewriterExtension;
use League\CommonMark\Environment\Environment;

$environment = new Environment();
$environment->addExtension(new LinkRewriterExtension([
    'base_uri' => 'https://docs.example.com',
]));
```

## Usage

No custom markdown directive is required; standard links/images are rewritten during document processing.

## Configuration

- `base_uri`: prepends a base URL to relative links.
- `map`: exact URL mapping array.
- `pattern`: regex replacement pair (`pattern` + `replacement`).
- `callback`: custom callable for advanced rewriting.

## Minimal Example

```markdown
[Guide](/getting-started)
![Logo](/assets/logo.svg)
```

## Development

This extension is actively developed in the [`alto/commonmark`](https://github.com/PhpAlto/commonmark) monorepo.

- [Report a bug](https://github.com/PhpAlto/commonmark/issues/new?labels=bug)
- [Suggest a feature](https://github.com/PhpAlto/commonmark/issues/new?labels=enhancement)
- [Open a Pull Request](https://github.com/PhpAlto/commonmark/pulls)

## License

MIT License — [Simon André](https://smnandre.dev) & [Alto](https://github.com/PhpAlto)
