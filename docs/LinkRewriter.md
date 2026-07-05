# LinkRewriter Extension

Rewrites the URLs of links and images after parsing, using any combination of
four strategies: a base URI, a lookup map, a regex pattern, or a callback.

## Basic Usage

```php
use Alto\CommonMark\Extension\LinkRewriter\LinkRewriterExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;

$environment = new Environment();
$environment->addExtension(new LinkRewriterExtension([
    'base_uri' => 'https://docs.example.com',
]));

$converter = new MarkdownConverter($environment);

echo $converter->convert('[Guide](/guide)');
// <a href="https://docs.example.com/guide">Guide</a>
```

## Configuration

Any subset of these keys may be set. When several are present they are applied
**in sequence** in the order below, each receiving the previous result.

| Key        | Type                              | Description                                                        |
|------------|-----------------------------------|--------------------------------------------------------------------|
| `base_uri` | `string`                          | Prepend a base URL to relative links. Absolute URLs are untouched. |
| `map`      | `array<string, string>`           | Literal find-and-replace on the URL.                               |
| `pattern`  | `array{pattern: string, replacement: string}` | `preg_replace`-style rewrite with capture groups.     |
| `callback` | `callable(string $url, Node $node): string` | Return the new URL. Receives the link/image node too.   |

Invalid config throws a `TypeError` at construction (for example a non-string
`base_uri`, or a `pattern` without both `pattern` and `replacement`).

```php
new LinkRewriterExtension([
    'base_uri' => 'https://docs.example.com',
    'map'      => ['/legacy' => '/current'],
    'pattern'  => ['pattern' => '#^/docs/(.+)$#', 'replacement' => 'https://docs.new.com/$1'],
    'callback' => fn (string $url): string => rtrim($url, '/'),
]);
```

Both `<a href>` and `<img src>` are rewritten.

## Output Examples

### Base URI

Config `['base_uri' => 'https://mysite.com']`:

```markdown
[Home](/)
[About](/about)
[External](https://example.com)
```

```html
<a href="https://mysite.com/">Home</a>
<a href="https://mysite.com/about">About</a>
<a href="https://example.com">External</a>
```

### Map

Config `['map' => ['/old-api' => '/api/v2']]`:

```markdown
[Old link](/old-api)
```

```html
<a href="/api/v2">Old link</a>
```

### Regex pattern

Config `['pattern' => ['pattern' => '#^/docs/(v\d+)/(.+)$#', 'replacement' => 'https://versioned-docs.com/$1/$2']]`:

```markdown
[Version 1](/docs/v1/intro)
```

```html
<a href="https://versioned-docs.com/v1/intro">Version 1</a>
```

### Images

Config `['base_uri' => 'https://mysite.com']`:

```markdown
![Alt text](/images/photo.jpg)
```

```html
<img src="https://mysite.com/images/photo.jpg" alt="Alt text" />
```

## See Also

- [Include](Include.md): compose documents from Markdown fragments
- [ContentSlicer](ContentSlicer.md): wrap heading sections in `<section>` tags

---

> **This package is part of the [alto/commonmark](https://github.com/PhpAlto/commonmark) monorepo.**
> This repository is a read-only split. To file issues, open pull requests, or contribute, use the main repository: **https://github.com/PhpAlto/commonmark**
