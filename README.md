# Alto CommonMark

Reusable `league/commonmark` extensions in a monorepo, installable as either the umbrella package `alto/commonmark` or as standalone per-extension packages.

## Installation

```bash
composer require alto/commonmark
```

`alto/commonmark` declares `replace` on all standalone packages, so dependency resolution stays compatible whether you install one or all.

| Extension | Description | GitHub | Packagist |
|-----------|-------------|--------|-----------|
| CodeBlockTitle | Titled fenced code blocks rendered as `<figure>` | [GitHub](https://github.com/PhpAlto/commonmark-code-block-title) | [Packagist](https://packagist.org/packages/alto/commonmark-code-block-title) |
| ContentSlicer | Wraps heading sections in semantic `<section>` elements | [GitHub](https://github.com/PhpAlto/commonmark-content-slicer) | [Packagist](https://packagist.org/packages/alto/commonmark-content-slicer) |
| HeadingLevel | Shift or remap heading levels across the document | [GitHub](https://github.com/PhpAlto/commonmark-heading-level) | [Packagist](https://packagist.org/packages/alto/commonmark-heading-level) |
| Import | Import file contents into code blocks with line ranges | [GitHub](https://github.com/PhpAlto/commonmark-import) | [Packagist](https://packagist.org/packages/alto/commonmark-import) |
| Include | Inline-include Markdown fragments for doc composition | [GitHub](https://github.com/PhpAlto/commonmark-include) | [Packagist](https://packagist.org/packages/alto/commonmark-include) |
| LinkRewriter | Rewrite links & images via base URI, map, or regex | [GitHub](https://github.com/PhpAlto/commonmark-link-rewriter) | [Packagist](https://packagist.org/packages/alto/commonmark-link-rewriter) |
| Source | Embed source files with line numbers and highlighting | [GitHub](https://github.com/PhpAlto/commonmark-source) | [Packagist](https://packagist.org/packages/alto/commonmark-source) |
| TableOfContents | Auto-generated TOC from headings via `@toc` | [GitHub](https://github.com/PhpAlto/commonmark-table-of-contents) | [Packagist](https://packagist.org/packages/alto/commonmark-table-of-contents) |
| Tabs | Accessible ARIA tabbed UI from a simple `@tabs` directive | [GitHub](https://github.com/PhpAlto/commonmark-tabs) | [Packagist](https://packagist.org/packages/alto/commonmark-tabs) |

## Extensions

### CodeBlockTitle
The detail that signals craft — adds a `title="..."` to any fenced code block and wraps it in a semantic `<figure>`/`<figcaption>`. One small thing that makes a doc site feel finished.

````markdown
```php title="hello.php"
echo "Hello";
```
````

```html
<figure class="code-block has-title" data-title="hello.php">
  <figcaption class="code-title">hello.php</figcaption>
  <pre><code class="language-php">echo "Hello";
</code></pre>
</figure>
```

[Doc](src/Extension/CodeBlockTitle/README.md) · [GitHub](https://github.com/alto/commonmark/tree/main/src/Extension/CodeBlockTitle) · [Packagist](https://packagist.org/packages/alto/commonmark-code-block-title)

---

### ContentSlicer
The rarest one in the set. Most processors stop at rendering headings as tags — this one restructures the entire document into a properly nested `<section>` tree, giving CSS selectors, JavaScript, and accessibility tooling something real to work with. No custom syntax needed.

```markdown
## Subtopic 1
More content.
## Subtopic 2
Final content.
```

```html
<section><h2>Subtopic 1</h2><p>More content.</p></section>
<section><h2>Subtopic 2</h2><p>Final content.</p></section>
```

[Doc](src/Extension/ContentSlicer/README.md) · [GitHub](https://github.com/alto/commonmark/tree/main/src/Extension/ContentSlicer) · [Packagist](https://packagist.org/packages/alto/commonmark-content-slicer)

---

### HeadingLevel
The one you don't need until you really do — then it's irreplaceable. Shifts, remaps, or transforms heading levels when embedding content from one context into another without heading hierarchy collisions.

```markdown
# Title
## Section
```

```html
<!-- with down: 1 -->
<h2>Title</h2>
<h3>Section</h3>
```

[Doc](src/Extension/HeadingLevel/README.md) · [GitHub](https://github.com/alto/commonmark/tree/main/src/Extension/HeadingLevel) · [Packagist](https://packagist.org/packages/alto/commonmark-heading-level)

---

### Import
Solves copy-paste drift between your docs and your source code. Pulls external file content directly into a code block — with line-range selection, language hinting, and depth-limited circular-import protection.

```markdown
@import "src/Auth.php" {lines: 1-30, lang: php}
```

```html
<pre><code class="language-php">// src/Auth.php lines 1–30
</code></pre>
```

[Doc](src/Extension/Import/README.md) · [GitHub](https://github.com/alto/commonmark/tree/main/src/Extension/Import) · [Packagist](https://packagist.org/packages/alto/commonmark-import)

---

### Include
The backbone of large documentation sets. Pulls in and fully parses markdown fragments inline — making one-file-per-section composition possible without a build system.

```markdown
@include "parts/intro.md"
```

```html
<h2>Introduction</h2>
<p>This is the introduction section.</p>
```

[Doc](src/Extension/Include/README.md) · [GitHub](https://github.com/alto/commonmark/tree/main/src/Extension/Include) · [Packagist](https://packagist.org/packages/alto/commonmark-include)

---

### LinkRewriter
Indispensable plumbing for any hosted documentation setup. Decouples your markdown from your deployment URL with a composable chain of rewrite rules — base URI, exact maps, regex, and custom callbacks — applied in sequence.

```markdown
[Guide](/getting-started)
![Logo](/assets/logo.svg)
```

```html
<!-- with base_uri: https://docs.example.com -->
<a href="https://docs.example.com/getting-started">Guide</a>
<img src="https://docs.example.com/assets/logo.svg" alt="Logo">
```

[Doc](src/Extension/LinkRewriter/README.md) · [GitHub](https://github.com/alto/commonmark/tree/main/src/Extension/LinkRewriter) · [Packagist](https://packagist.org/packages/alto/commonmark-link-rewriter)

---

### Source
The flagship of the set. Embeds a real file — not a copy — directly into your documentation, with syntax detection, line-range selection, line numbers, and per-line highlighting. Your docs stay in sync with your code by definition.

```markdown
@source "src/Service.php" {lines: 9-11, numbers: true, highlight: "9,11"}
```

```html
<div class="source-block">
  <div class="source-path">src/Service.php</div>
  <pre><code class="language-php"><span class="line highlighted"><span class="line-number">9</span>    public function add(int $a, int $b): int</span>
<span class="line"><span class="line-number">10</span>    {</span>
<span class="line highlighted"><span class="line-number">11</span>        return $a + $b;</span></code></pre>
</div>
```

[Doc](src/Extension/Source/README.md) · [GitHub](https://github.com/alto/commonmark/tree/main/src/Extension/Source) · [Packagist](https://packagist.org/packages/alto/commonmark-source)

---

### TableOfContents
A must-have for any document longer than a page. Drop `@toc` where you want the contents list — headings are collected, IDs assigned, and a navigable list rendered in one pass.

```markdown
@toc {min: 2}
## Introduction
## Setup
```

```html
<div class="table-of-contents" id="toc">
  <ul>
    <li><a href="#introduction">Introduction</a></li>
    <li><a href="#setup">Setup</a></li>
  </ul>
</div>
```

[Doc](src/Extension/TableOfContents/README.md) · [GitHub](https://github.com/alto/commonmark/tree/main/src/Extension/TableOfContents) · [Packagist](https://packagist.org/packages/alto/commonmark-table-of-contents)

---

### Tabs
One directive, fully accessible tabbed UI, zero JavaScript to write. Wraps content in proper ARIA `tablist`/`tab`/`tabpanel` roles with a self-contained switching script.

````markdown
@tabs
@tab "PHP"
```php
echo 'Hello';
```
@tab "JS"
```js
console.log('Hello');
```
@endtabs
````

```html
<div class="tab-group" data-tabs-id="tabs-1">
  <div class="tab-list" role="tablist">
    <button class="tab active" role="tab" aria-selected="true"  aria-controls="tabs-1-panel-0">PHP</button>
    <button class="tab"        role="tab" aria-selected="false" aria-controls="tabs-1-panel-1">JS</button>
  </div>
  <div class="tab-panels">
    <div class="tab-panel" id="tabs-1-panel-0" role="tabpanel">…</div>
    <div class="tab-panel" id="tabs-1-panel-1" role="tabpanel">…</div>
  </div>
</div>
```

[Doc](src/Extension/Tabs/README.md) · [GitHub](https://github.com/alto/commonmark/tree/main/src/Extension/Tabs) · [Packagist](https://packagist.org/packages/alto/commonmark-tabs)

## Support

If Alto CommonMark is useful to your project, [sponsoring on GitHub](https://github.com/sponsors/smnandre) is a great way to support continued development — and it's always appreciated.

## License

MIT. See [LICENSE](LICENSE).
