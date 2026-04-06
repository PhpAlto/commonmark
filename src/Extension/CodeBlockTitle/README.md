# CodeBlockTitle Extension

## Summary

The detail that signals craft. Adds a `title="..."` attribute to any fenced code block info string and wraps the result in a semantic `<figure>`/`<figcaption>` pair. A small delta — but the kind of finish that separates a polished documentation site from a functional one.

## Installation

### composer require

```bash
composer require alto/commonmark
```

### Registration

```php
use Alto\CommonMark\Extension\CodeBlockTitle\CodeBlockTitleExtension;
use League\CommonMark\Environment\Environment;

$environment = new Environment();
$environment->addExtension(new CodeBlockTitleExtension());
```

## Usage

````markdown
```php title="src/App.php"
<?php

echo "Hello";
```
````

## Configuration

- `new CodeBlockTitleExtension()` uses the default CommonMark fenced-code renderer.
- You can inject a custom base renderer via the constructor when needed.

## Minimal Example

````markdown
```javascript title="app.js"
console.log('Hello');
```
````

## Development

This extension is actively developed in the [`alto/commonmark`](https://github.com/PhpAlto/commonmark) monorepo.

- [Report a bug](https://github.com/PhpAlto/commonmark/issues/new?labels=bug)
- [Suggest a feature](https://github.com/PhpAlto/commonmark/issues/new?labels=enhancement)
- [Open a Pull Request](https://github.com/PhpAlto/commonmark/pulls)

## License

MIT License — [Simon André](https://smnandre.dev) & [Alto](https://github.com/PhpAlto)
