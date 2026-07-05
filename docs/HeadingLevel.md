# HeadingLevel Extension

Rewrites heading levels across a document with one of three strategies: an
explicit map, a uniform shift, or a callback.

## Basic Usage

```php
use Alto\CommonMark\Extension\HeadingLevel\HeadingLevelExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;

$environment = new Environment();
// Shift every heading down by one level (h1 becomes h2, h2 becomes h3, ...)
$environment->addExtension(new HeadingLevelExtension(['down' => 1]));

$converter = new MarkdownConverter($environment);
```

## Configuration

Exactly **one** strategy applies, chosen by this priority: `map`, then `down`,
then `callback`. If more than one key is set, the first present in that order
wins; they are not combined.

| Key        | Type                   | Description                                                                 |
|------------|------------------------|-----------------------------------------------------------------------------|
| `map`      | `array<int, int>`      | Explicit level-to-level mapping. Levels absent from the map are left unchanged. |
| `down`     | `int`                  | Uniform shift. Positive moves deeper (h1 to h2); negative moves up (h2 to h1). |
| `callback` | `callable(int): ?int`  | Receives the current level, returns the new level, or `null` to leave it unchanged. |

```php
// Map
new HeadingLevelExtension(['map' => [1 => 2, 2 => 3]]);

// Shift up by 2 (negative down)
new HeadingLevelExtension(['down' => -2]);

// Callback
new HeadingLevelExtension(['callback' => fn (int $level): int => min($level + 1, 6)]);
```

> Levels are written as-is. Nothing clamps the result, so a shift can produce
> `h0` or `h7`+. Guard the range yourself with a callback if that matters:
> `fn (int $l): int => max(1, min($l + 1, 6))`.

## Output Examples

### Shift down

Input:

```markdown
# Main Title

## Subtitle

### Details
```

Config `['down' => 1]`:

```html
<h2>Main Title</h2>
<h3>Subtitle</h3>
<h4>Details</h4>
```

### Shift up

Input:

```markdown
### Nested Heading

#### Sub-section
```

Config `['down' => -2]`:

```html
<h1>Nested Heading</h1>
<h2>Sub-section</h2>
```

### Map

Input:

```markdown
# Title

## Section

### Subsection
```

Config `['map' => [1 => 2, 2 => 3, 3 => 4]]`:

```html
<h2>Title</h2>
<h3>Section</h3>
<h4>Subsection</h4>
```

Levels missing from the map keep their original value.

## See Also

- [ContentSlicer](ContentSlicer.md): wrap heading sections in `<section>` tags
- [Include](Include.md): compose documents from Markdown fragments

---

> **This package is part of the [alto/commonmark](https://github.com/PhpAlto/commonmark) monorepo.**
> This repository is a read-only split. To file issues, open pull requests, or contribute, use the main repository: **https://github.com/PhpAlto/commonmark**
