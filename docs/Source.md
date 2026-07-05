# Source Extension

Displays a source file as a highlighted code block at the `@source` marker
position. Supports line ranges, line numbers, and per-line highlighting.

## Basic Usage

```php
use Alto\CommonMark\Extension\Source\SourceExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;

$environment = new Environment();
$environment->addExtension(new SourceExtension(__DIR__));

$converter = new MarkdownConverter($environment);
```

```markdown
@source "src/Calculator.php"
```

```html

<div class="source-block">
    <div class="source-path">src/Calculator.php</div>
    <pre><code class="language-php">&lt;?php
// file content here
</code></pre>
</div>
```

## Syntax

```markdown
@source "path/to/file.php"
@source "path/to/file.php" {lines: 9-11}
@source "path/to/file.php" {title: "Add method", lines: 9-11, numbers: true,
highlight: "10"}
```

The directive must be at column 0 (no indentation). The path is relative to
`basePath`.

### Options

| Option      | Type   | Example                         | Description                                          |
|-------------|--------|---------------------------------|------------------------------------------------------|
| `lines`     | range  | `{lines: 5}` or `{lines: 9-15}` | Extract specific lines (1-indexed)                   |
| `lang`      | string | `{lang: php}`                   | Override the auto-detected language                  |
| `title`     | string | `{title: "My method"}`          | Heading displayed above the code                     |
| `numbers`   | bool   | `{numbers: true}`               | Display original line numbers                        |
| `highlight` | string | `{highlight: "1,3-5"}`          | Lines to highlight (comma-separated, ranges allowed) |

## Constructor

```php
new SourceExtension(
    string $basePath = '.',
    array $allowedExtensions = [],
    bool $escapeHtml = true,
    int $maxFileSize = 1048576,
)
```

| Parameter           | Type   | Default   | Description                                          |
|---------------------|--------|-----------|------------------------------------------------------|
| `basePath`          | string | `'.'`     | Directory all source paths are resolved against      |
| `allowedExtensions` | array  | `[]`      | Whitelist of permitted extensions; empty = allow all |
| `escapeHtml`        | bool   | `true`    | HTML-escape file content                             |
| `maxFileSize`       | int    | `1048576` | Maximum file size in bytes (default 1 MB)            |

## Output Examples

### Basic source block

```markdown
@source "src/Calculator.php" {lines: 9-11}
```

```html

<div class="source-block">
    <div class="source-path">src/Calculator.php</div>
    <pre><code class="language-php">    public function add(int $a, int $b): int
    {
        return $a + $b;
    }</code></pre>
</div>
```

### With title, line numbers and highlighting

```markdown
@source "src/Calculator.php" {title: "Add method", lines: 9-11, numbers: true,
highlight: "10"}
```

```html

<div class="source-block">
    <div class="source-title">Add method</div>
    <div class="source-path">src/Calculator.php</div>
    <pre><code class="language-php"><span class="line"><span
            class="line-number">9</span>    public function add(int $a, int $b): int</span>
<span class="line highlighted"><span class="line-number">10</span>    {</span>
<span class="line"><span
        class="line-number">11</span>        return $a + $b;</span></code></pre>
</div>
```

## Language Auto-Detection

The language is detected from the file extension automatically. Recognised
languages include:

`php`, `js` / `jsx`, `ts` / `tsx`, `py`, `rb`, `go`, `rs`, `java`, `c`, `cpp`,
`cs`, `swift`, `kt`, `scala`, `r`, `sh` / `bash`, `ps1`, `sql`, `html`, `xml`,
`css`, `scss`, `less`, `json`, `yaml` / `yml`, `toml`, `ini`, `md`, `rst`,`tex`,
`dockerfile`, `makefile`, `cmake`, `conf` (nginx/apache), `vim`, `lua`,`pl`,
`asm`, `diff`, `csv`, and more.

Use `{lang: ...}` to override the detected language.

## Security

All paths are resolved with `realpath()` and validated against `basePath`:

- Absolute paths are rejected
- Path traversal (`../`) is blocked
- If `allowedExtensions` is non-empty, only listed extensions are accepted
- Files exceeding `maxFileSize` are rejected

## Error Output

```html

<div class="source-error">Source error: File not found: missing.php</div>
<div class="source-error">Source error: Path not allowed: ../../.env</div>
<div class="source-error">Source error: File type not allowed: .env</div>
<div class="source-error">Source error: File too large: 2.50MB (max: 1.00MB)
</div>
```

## See Also

- [Import](Import.md): embeds raw file content (plain text or code block)
- [Include](Include.md): includes and re-parses a Markdown file

---

> **This package is part of
the [alto/commonmark](https://github.com/PhpAlto/commonmark) monorepo.**  
> This repository is a read-only split. To file issues, open pull requests, or contribute, use the main repository: **https://github.com/PhpAlto/commonmark**
