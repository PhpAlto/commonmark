# Import Extension

Embeds raw file content at the `@import` marker position, optionally scoped to a
line range, with optional language and indent options.

## Basic Usage

```php
use Alto\CommonMark\Extension\Import\ImportExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;

$environment = new Environment();
$environment->addExtension(new ImportExtension(__DIR__ . '/content'));

$converter = new MarkdownConverter($environment);
```

```markdown
@import "snippet.md"
```

```html
<p>The raw content of snippet.md, inserted as-is.</p>
```

## Syntax

```markdown
@import "path/to/file.md"
@import "path/to/file.php" {lines: 1-10}
@import "path/to/file.php" {lines: 1-10, lang: php, indent: 2}
```

The directive must be at column 0 (no indentation). The path is relative to
`basePath`.

### Options

| Option   | Type   | Example                        | Description                            |
|----------|--------|--------------------------------|----------------------------------------|
| `lines`  | range  | `{lines: 5}` or `{lines: 2-8}` | Extract specific lines (1-indexed)     |
| `lang`   | string | `{lang: php}`                  | Language for syntax highlighting       |
| `indent` | int    | `{indent: 4}`                  | Number of leading spaces on every line |

When `lang` is provided, the imported content is rendered as a fenced code
block.

## Constructor

```php
new ImportExtension(string $basePath = '.', int $maxDepth = 10)
```

| Parameter  | Type   | Default | Description                                     |
|------------|--------|---------|-------------------------------------------------|
| `basePath` | string | `'.'`   | Directory all import paths are resolved against |
| `maxDepth` | int    | `10`    | Maximum recursion depth for nested imports      |

## Output Examples

### Plain import

```markdown
@import "intro.md"
```

```html
<p>Welcome to the guide.</p>
```

The file content is embedded verbatim; it is **not** re-parsed as Markdown.

### Code import with options

```markdown
@import "src/Calculator.php" {lines: 9-11, lang: php, indent: 2}
```

```html

<pre><code class="language-php">  public function add(int $a, int $b): int
  {
      return $a + $b;
  }</code></pre>
```

## Security

All paths are resolved with `realpath()` and validated against `basePath`:

- Absolute paths are rejected
- Path traversal (`../`) is blocked
- Circular imports (same file twice in a chain) are detected
- Depth limit prevents infinite recursion

## Error Output

Errors render as an inline `<div>` so the rest of the page remains intact:

```html

<div class="import-error">Import error: File not found: missing.md</div>
<div class="import-error">Import error: Path not allowed: ../../etc/passwd</div>
<div class="import-error">Import error: Circular import detected</div>
<div class="import-error">Import error: Max import depth exceeded</div>
```

## See Also

- [Include](Include.md): re-parses the included file as Markdown
- [Source](Source.md): displays source code with syntax highlighting

---

> **This package is part of
the [alto/commonmark](https://github.com/PhpAlto/commonmark) monorepo.**  
> This repository is a read-only split. To file issues, open pull requests, or contribute, use the main repository: **https://github.com/PhpAlto/commonmark**
