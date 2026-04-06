# Include Extension

Includes an external Markdown file at the `@include` marker position. The file
is loaded and **re-parsed as Markdown**, making it ideal for composing documents
from reusable content blocks.

## Basic Usage

```php
use Alto\CommonMark\Extension\Include\IncludeExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;

$environment = new Environment();
$environment->addExtension(new IncludeExtension(__DIR__ . '/content'));

$converter = new MarkdownConverter($environment);
```

```markdown
@include "introduction.md"
```

```html
<h2>Introduction</h2>
<p>This is the introduction section.</p>
```

## Syntax

```markdown
@include "path/to/file.md"
@include "path/to/file.md" {lines: 2-5}
```

The directive must be at column 0 (no indentation). The path is relative to
`basePath`.

### Options

| Option  | Type  | Example                        | Description                                       |
|---------|-------|--------------------------------|---------------------------------------------------|
| `lines` | range | `{lines: 5}` or `{lines: 2-5}` | Extract specific lines before parsing (1-indexed) |

## Constructor

```php
new IncludeExtension(
    string $basePath = '.',
    int $maxDepth = 10,
    array $allowedExtensions = ['md', 'markdown'],
    int $maxFileSize = 1048576,
)
```

| Parameter           | Type   | Default              | Description                                      |
|---------------------|--------|----------------------|--------------------------------------------------|
| `basePath`          | string | `'.'`                | Directory all include paths are resolved against |
| `maxDepth`          | int    | `10`                 | Maximum recursion depth for nested includes      |
| `allowedExtensions` | array  | `['md', 'markdown']` | Whitelist of permitted file extensions           |
| `maxFileSize`       | int    | `1048576`            | Maximum file size in bytes (default 1 MB)        |

## Output Examples

### Basic include

```markdown
@include "header.md"
```

`header.md`:

```markdown
## Site Header

Welcome to the documentation.
```

Output:

```html
<h2>Site Header</h2>
<p>Welcome to the documentation.</p>
```

### Scoped to a line range

```markdown
@include "changelog.md" {lines: 1-5}
```

Only the first five lines of `changelog.md` are included and parsed.

## Import vs. Include

| Feature                    | `@import`     | `@include` |
|----------------------------|---------------|------------|
| Re-parses as Markdown      | No — raw text | **Yes**    |
| `lang` option (code block) | Yes           | No         |
| `indent` option            | Yes           | No         |
| `allowedExtensions` filter | No            | Yes        |
| `maxFileSize` limit        | No            | Yes        |

Use `@include` when the file contains Markdown you want rendered.  
Use `@import` when you want raw text or code blocks.

## Security

All paths are resolved with `realpath()` and validated against `basePath`:

- Absolute paths are rejected
- Path traversal (`../`) is blocked
- Only extensions in `allowedExtensions` are accepted
- Files exceeding `maxFileSize` are rejected
- Circular includes and depth limits are enforced

## Error Output

```html

<div class="include-error">Include error: File not found: missing.md</div>
<div class="include-error">Include error: Path not allowed: ../../etc/passwd
</div>
<div class="include-error">Include error: File type not allowed: .txt</div>
<div class="include-error">Include error: File too large: 2.50MB (max: 1.00MB)
</div>
```

## See Also

- [Import](Import.md) — embeds raw file content (not re-parsed)
- [Source](Source.md) — displays source code with syntax highlighting

---

> **This package is part of
the [alto/commonmark](https://github.com/PhpAlto/commonmark) monorepo.**  
> This repository is a read-only split — to file issues, open pull requests, or
> contribute, please use the main repository: *
*https://github.com/PhpAlto/commonmark**
