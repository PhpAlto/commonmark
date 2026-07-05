# CodeBlockTitle Extension

Reads a `title="..."` attribute from a fenced code block's info string and wraps
the block in a `<figure>` with a `<figcaption>`.

## Basic Usage

```php
use Alto\CommonMark\Extension\CodeBlockTitle\CodeBlockTitleExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;

$environment = new Environment();
$environment->addExtension(new CodeBlockTitleExtension());

$converter = new MarkdownConverter($environment);
```

````markdown
```php title="example.php"
echo "Hello, World!";
```
````

```html

<figure class="code-block has-title" data-title="example.php">
<figcaption class="code-title">example.php</figcaption>
<pre><code class="language-php">echo &quot;Hello, World!&quot;;
</code></pre>
</figure>
```

A block without a title renders as the usual `<pre><code>` with no `<figure>`.

## Syntax

Add the attribute to the info string, right after the language:

````markdown
```javascript title="app.js"
console.log("hi");
```
````

- `filename="..."` is accepted as an alias for `title`.
- Escape quotes inside the title with a backslash: `title="a \"quoted\" name"`.

## Constructor

```php
new CodeBlockTitleExtension(?NodeRendererInterface $baseRenderer = null)
```

| Parameter      | Type                          | Default                | Description                                        |
|----------------|-------------------------------|------------------------|----------------------------------------------------|
| `baseRenderer` | `?NodeRendererInterface`      | `new FencedCodeRenderer()` | Renderer used to build the inner `<pre><code>`. Override to compose with another code renderer. |

## Styling

The output uses stable hooks: `.code-block.has-title`, `.code-title`, and a
`data-title` attribute.

```css
.code-block.has-title {
    border: 1px solid #e0e0e0;
    border-radius: 4px;
}

.code-title {
    font: 600 12px/1 ui-monospace, monospace;
    color: #666;
    padding: 8px 12px;
}
```

## See Also

- [Source](Source.md): display a source file with line numbers and highlighting
- [Import](Import.md): embed raw file content into a code block

---

> **This package is part of the [alto/commonmark](https://github.com/PhpAlto/commonmark) monorepo.**
> This repository is a read-only split. To file issues, open pull requests, or contribute, use the main repository: **https://github.com/PhpAlto/commonmark**
