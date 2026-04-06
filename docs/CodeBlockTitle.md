# CodeBlockTitle Extension

Parses `title="..."` attributes from fenced code block info strings and renders
them as `<figcaption>` elements inside `<figure>` tags.

## Basic Usage

```php
use Alto\CommonMark\Extension\CodeBlockTitle\CodeBlockTitleExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;

$environment = new Environment();
$environment->addExtension(new CodeBlockTitleExtension());

$converter = new MarkdownConverter($environment);

$markdown = <<<'MD'
    ```php title="example.php"
    echo "Hello, World!";
    ```
MD;

echo $converter->convert($markdown);
```

## Features

- Parses `title="..."` from fenced code block info strings
- Wraps code blocks in `<figure>` elements with `<figcaption>`
- Preserves the language specifier alongside the title
- Works with all code block languages
- Integrates seamlessly with other extensions

## Syntax

Add a `title="..."` attribute to your code block's info string:

```markdown
    ```javascript title="app.js"
    function greet(name) {
      console.log(`Hello, ${name}!`);
    }
    ```
```

The `title` attribute can contain any text and supports special characters.
Quotes within the title should be escaped:

```markdown
    ```bash title="script with \"quotes\""
    echo "test"
    ```
```

## Output

The extension transforms:

```markdown
    ```php title="example.php"
    echo "Hello, World!";
    ```
```

Into:

```html

<figure>
    <figcaption>example.php</figcaption>
    <pre><code class="language-php">echo "Hello, World!";</code></pre>
</figure>
```

## Configuration

The extension requires no configuration and can be registered without
parameters:

```php
$environment->addExtension(new CodeBlockTitleExtension());
```

Optionally, you can provide a custom base renderer:

```php
use League\CommonMark\Extension\CommonMark\Renderer\Block\FencedCodeRenderer;

$baseRenderer = new FencedCodeRenderer();
$environment->addExtension(new CodeBlockTitleExtension($baseRenderer));
```

## Advanced Usage

### With Multiple Extensions

Combine with other extensions for enhanced functionality:

```php
$environment = new Environment();
$environment->addExtension(new CommonMarkCoreExtension());
$environment->addExtension(new CodeBlockTitleExtension());
$environment->addExtension(new ContentSlicerExtension());

$converter = new MarkdownConverter($environment);
```

### Styling the Figure

The extension generates semantic HTML that can be styled with CSS:

```css
figure {
  border: 1px solid #E0E0E0;
  border-radius: 4px;
  padding: 12px;
  margin: 16px 0;
}

figcaption {
  font-size: 12px;
  font-weight: 600;
  color: #666666;
  margin-bottom: 8px;
  font-family: 'Monaco', 'Menlo', monospace;
}

figure code {
  display: block;
  overflow-x: auto;
}
```

## Implementation Details

- **Pattern**: Renderer-based decorator
- **Priority**: 10 (runs before default renderers)
- **Custom Nodes**: None (uses standard `FencedCode` node)
- **Event Listeners**: None

The extension decorates the default `FencedCodeRenderer` and extracts the title
attribute during rendering. This approach ensures compatibility with other
rendering extensions.

## Examples

### Multiple Code Blocks with Titles

```markdown
    ```javascript title="index.js"
    import express from 'express';
    const app = express();
    app.listen(3000);
    ```
    
    ```html title="index.html"
    <!DOCTYPE html>
    <html>
      <head><title>App</title></head>
      <body>Hello World</body>
    </html>
    ```
```

### Without Title

Blocks without a title attribute work normally:

```markdown
    ```python
    def hello():
        print("Hello, World!")
    ```
```

Renders as:

```html

<pre><code class="language-python">def hello():
    print("Hello, World!")</code></pre>
```

### With Complex Titles

Titles can include file paths and descriptions:

```markdown
    ```bash title="src/scripts/deploy.sh"
    #!/bin/bash
    ./build.sh && ./deploy.sh
    ```
```

## Troubleshooting

### Title not appearing

Ensure the `title="..."` attribute is in the info string (the part immediately
after the opening ````):

```markdown
    <!-- Correct -->
    ```php title="file.php"
    code here
    ```
    
    <!-- Wrong - title is in code content, not info string -->
    ```php
    // title="file.php"
    code here
    ```
```

### Quotes in titles

Use backslash escaping for quotes within titles:

```markdown
    ```bash title="script with \"quotes\""
    echo "test"
    ```
```

## See Also

- [league/commonmark documentation](https://commonmark.thephpleague.com/)

---

> **This package is part of
the [alto/commonmark](https://github.com/PhpAlto/commonmark) monorepo.**  
> This repository is a read-only split — to file issues, open pull requests, or
> contribute, please use the main repository: *
*https://github.com/PhpAlto/commonmark**
