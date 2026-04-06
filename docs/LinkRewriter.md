# LinkRewriter Extension

Rewrites URLs in links and images through various strategies: base URI
prepending, simple mapping, regex patterns, or custom callbacks.

## Basic Usage

```php
use Alto\CommonMark\Extension\LinkRewriter\LinkRewriterExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;

$environment = new Environment();

$config = [
    'link_rewriter' => [
        'base_uri' => 'https://docs.example.com'
    ]
];

$environment->addExtension(new LinkRewriterExtension($config['link_rewriter']));

$converter = new MarkdownConverter($environment);

$markdown = "[Guide](/guide)";
echo $converter->convert($markdown);  // <a href="https://docs.example.com/guide">Guide</a>
```

## Features

- **Base URI**: Prepend a base URL to relative links
- **URL Mapping**: Simple find-and-replace URL transformations
- **Regex Patterns**: Complex URL rewriting with regex and capture groups
- **Callbacks**: Custom PHP functions for dynamic rewriting
- **Composable**: Chain multiple rewriters in sequence
- **Link & Image Support**: Works on both `<a>` href and `<img>` src attributes

## Configuration Options

### 1. Base URI

Prepend a base URL to relative links:

```php
new LinkRewriterExtension([
    'base_uri' => 'https://docs.example.com'
])
```

Input: `[Link](/api/users)`
Output: `<a href="https://docs.example.com/api/users">Link</a>`

Absolute URLs are not modified:
Input: `[External](https://example.org)`
Output: `<a href="https://example.org">External</a>`

### 2. URL Mapping

Simple find-and-replace mapping:

```php
new LinkRewriterExtension([
    'map' => [
        '/old-path' => '/new-path',
        'example.com' => 'example.org',
    ]
])
```

### 3. Regex Patterns

Use regex patterns with replacements:

```php
new LinkRewriterExtension([
    'pattern' => [
        'pattern' => '/^\/docs\/(.+)$/',
        'replacement' => 'https://docs.example.com/$1'
    ]
])
```

Input: `/docs/api/users`
Output: `https://docs.example.com/api/users`

### 4. Custom Callback

Use a function for complex logic:

```php
new LinkRewriterExtension([
    'callback' => function(string $url, \League\CommonMark\Node\Node $node): string {
        // Custom logic
        if (str_starts_with($url, '/')) {
            return 'https://mysite.com' . $url;
        }
        return $url;
    }
])
```

### 5. Combined Configuration

Rewriters are applied in order:

```php
new LinkRewriterExtension([
    'base_uri' => 'https://docs.example.com',
    'map' => [
        '/legacy' => '/current'
    ],
    'pattern' => [
        'pattern' => '/^https:\/\/docs\.old\.com\/(.+)$/',
        'replacement' => 'https://docs.new.com/$1'
    ],
    'callback' => function(string $url): string {
        // Final transformations
        return $url;
    }
])
```

Rewriters are applied sequentially, so the output of one becomes the input to
the next.

## Output Examples

### Base URI

Input markdown:

```markdown
[Home](/)
[About](/about)
[External](https://example.com)
```

Config: `['base_uri' => 'https://mysite.com']`

Output:

```html
<a href="https://mysite.com/">Home</a>
<a href="https://mysite.com/about">About</a>
<a href="https://example.com">External</a>
```

### URL Mapping

Input markdown:

```markdown
[Old link](/old-api)
[File](readme.txt)
```

Config:

```php
'map' => [
    '/old-api' => '/api/v2',
    'readme.txt' => 'README.md'
]
```

Output:

```html
<a href="/api/v2">Old link</a>
<a href="README.md">File</a>
```

### Regex Pattern

Input markdown:

```markdown
[Version 1](/docs/v1/intro)
[Version 2](/docs/v2/intro)
```

Config:

```php
'pattern' => [
    'pattern' => '/^\/docs\/(v\d+)\/(.+)$/',
    'replacement' => 'https://versioned-docs.com/$1/$2'
]
```

Output:

```html
<a href="https://versioned-docs.com/v1/intro">Version 1</a>
<a href="https://versioned-docs.com/v2/intro">Version 2</a>
```

## Advanced Usage

### Content Distribution

Rewrite links when distributing content across domains:

```php
// Main site
$mainEnv = new Environment();
$mainEnv->addExtension(new LinkRewriterExtension([
    'base_uri' => 'https://main.com'
]));

// CDN mirror
$cdnEnv = new Environment();
$cdnEnv->addExtension(new LinkRewriterExtension([
    'base_uri' => 'https://cdn.main.com'
]));

// Each uses the same markdown but different rewriting
$markdown = file_get_contents('article.md');
$mainHtml = new MarkdownConverter($mainEnv)->convert($markdown);
$cdnHtml = new MarkdownConverter($cdnEnv)->convert($markdown);
```

### Version-Aware Links

Create version-specific documentation links:

```php
function createVersionConverter(string $version): MarkdownConverter {
    $env = new Environment();
    $env->addExtension(new LinkRewriterExtension([
        'pattern' => [
            'pattern' => '/^(\/docs\/)/',
            'replacement' => "/docs/$version/"
        ]
    ]));
    return new MarkdownConverter($env);
}

$v1Converter = createVersionConverter('1.0');
$v2Converter = createVersionConverter('2.0');
```

### Link Validation & Logging

Use callbacks for tracking:

```php
$links = [];

new LinkRewriterExtension([
    'callback' => function(string $url): string {
        global $links;
        $links[] = $url;
        return $url;
    }
])

// Later: analyze collected links
foreach ($links as $url) {
    // Validate, log, etc.
}
```

### Anchor Resolution

Resolve local anchors to full URLs:

```php
new LinkRewriterExtension([
    'callback' => function(string $url): string {
        if (str_starts_with($url, '#')) {
            // Local anchor
            return 'page.html' . $url;
        }
        return $url;
    }
])
```

Input: `[See below](#section)`
Output: `<a href="page.html#section">See below</a>`

### Proxy URLs

Route all external links through a proxy:

```php
new LinkRewriterExtension([
    'pattern' => [
        'pattern' => '/^https?:\/\/(?!mysite\.com)(.+)$/',
        'replacement' => 'https://mysite.com/proxy?url=$0'
    ]
])
```

### Multi-Domain Migration

Migrate links from old to new domain:

```php
new LinkRewriterExtension([
    'map' => [
        'old-domain.com' => 'new-domain.com',
        'old-cdn.com' => 'new-cdn.com',
    ]
])
```

## Common Patterns

### Static Site with Assets

```php
$env = new Environment();
$env->addExtension(new LinkRewriterExtension([
    'base_uri' => '/generated',  // Relative base
    'map' => [
        '/images/' => '/assets/img/',
        '/styles/' => '/assets/css/',
    ]
]));
```

### API Documentation Link Prefix

```php
new LinkRewriterExtension([
    'callback' => function(string $url): string {
        if (preg_match('/^\/api/', $url)) {
            return 'https://api.example.com' . $url;
        }
        return $url;
    }
])
```

### Relative to Absolute URLs

```php
new LinkRewriterExtension([
    'callback' => function(string $url): string {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            // Relative URL
            return 'https://mysite.com' . (str_starts_with($url, '/') ? '' : '/') . $url;
        }
        return $url;
    }
])
```

## Working with Images

The extension also rewrites image URLs:

```markdown
![Alt text](/images/photo.jpg)
```

With config `['base_uri' => 'https://mysite.com']`:

```html
<img src="https://mysite.com/images/photo.jpg" alt="Alt text"/>
```

## Implementation Details

- **Pattern**: Event-based processor
- **Event**: `DocumentParsedEvent`
- **Nodes Modified**: Link and Image nodes
- **Rewriter Interface**: Composable functions taking URL and Node

The extension walks the AST after parsing, finds all Link and Image nodes, and
applies configured rewriters in sequence.

## Troubleshooting

### Rewriter not applied

Ensure the extension is registered before creating the converter:

```php
$env = new Environment();
$env->addExtension(
    new LinkRewriterExtension(['base_uri' => 'https://example.com'])
);
$converter = new MarkdownConverter($env);  // Must be after
```

### Regex not matching

Test your pattern separately:

```php
$pattern = '/^\/docs\/(.+)$/';
$url = '/docs/api/users';

if (preg_match($pattern, $url, $matches)) {
    // Pattern matches
}
```

### Multiple rewriters conflict

Remember rewriters apply in order. Later ones can override earlier ones:

```php
[
    'base_uri' => 'https://example.com',  // Applied first
    'map' => ['https://example.com/old' => 'https://new.com'],  // Overrides base_uri for this URL
    'callback' => function($url) { ... }  // Applied last
]
```

### Absolute URLs getting modified

Check your conditions:

```php
'callback' => function(string $url): string {
    // Skip already absolute URLs
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }
    // Only rewrite relative URLs
    return 'https://mysite.com' . $url;
}
```

## See Also

- [HeadingLevel Extension](HeadingLevel.md) - Adjust heading levels
- [league/commonmark documentation](https://commonmark.thephpleague.com/)

---

> **This package is part of
the [alto/commonmark](https://github.com/PhpAlto/commonmark) monorepo.**  
> This repository is a read-only split — to file issues, open pull requests, or
> contribute, please use the main repository: *
*https://github.com/PhpAlto/commonmark**
