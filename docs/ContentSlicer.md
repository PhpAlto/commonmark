# ContentSlicer Extension

Wraps heading sections in nested `<section>` elements to give the document a
semantic outline. By default the top-level heading stays at the document root and
every deeper heading opens a section.

## Basic Usage

```php
use Alto\CommonMark\Extension\ContentSlicer\ContentSlicerExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;

$environment = new Environment();
$environment->addExtension(new ContentSlicerExtension());

$converter = new MarkdownConverter($environment);
```

```markdown
# Main Topic

Intro.

## Subtopic

Details.
```

```html

<h1>Main Topic</h1>
<p>Intro.</p>
<section>
    <h2>Subtopic</h2>
    <p>Details.</p>
</section>
```

The `<h1>` is not wrapped: at the default threshold only headings deeper than
level 1 open a section.

## Constructor

```php
new ContentSlicerExtension(int $minSectionLevel = 1)
```

| Parameter         | Type | Default | Description                                                                                              |
|-------------------|------|---------|----------------------------------------------------------------------------------------------------------|
| `minSectionLevel` | int  | `1`     | Only headings deeper than this level open a section. `1` wraps h2+ (h1 stays at root); `0` wraps h1 too; `2` wraps only h3+. |

## Output Examples

### Nested hierarchy

Input:

```markdown
# Main

Content 1

## Sub 1

Content 2

### Sub 1.1

Content 3

## Sub 2

Content 4
```

Output:

```html

<h1>Main</h1>
<p>Content 1</p>
<section>
    <h2>Sub 1</h2>
    <p>Content 2</p>
    <section>
        <h3>Sub 1.1</h3>
        <p>Content 3</p>
    </section>
</section>
<section>
    <h2>Sub 2</h2>
    <p>Content 4</p>
</section>
```

### Wrapping every heading

With `new ContentSlicerExtension(0)`, level-1 headings open a section as well:

```html

<section>
    <h1>Main</h1>
    <p>Content 1</p>
    <section>
        <h2>Sub 1</h2>
        <p>Content 2</p>
    </section>
</section>
```

Content that appears before the first wrapped heading stays at the document root.

## See Also

- [HeadingLevel](HeadingLevel.md): adjust heading levels before sectioning
- [TableOfContents](TableOfContents.md): generate a TOC from the same headings

---

> **This package is part of the [alto/commonmark](https://github.com/PhpAlto/commonmark) monorepo.**
> This repository is a read-only split. To file issues, open pull requests, or contribute, use the main repository: **https://github.com/PhpAlto/commonmark**
