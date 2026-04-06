# ContentSlicer Extension

Automatically divides a document into nested `<section>` elements based on
heading hierarchy, creating a semantic document structure.

## Basic Usage

```php
use Alto\CommonMark\Extension\ContentSlicer\ContentSlicerExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;

$environment = new Environment();
$environment->addExtension(new ContentSlicerExtension());

$converter = new MarkdownConverter($environment);

$markdown = <<<'MD'
# Main Topic

Content here.

## Subtopic 1

More content.

## Subtopic 2

Final content.
MD;

echo $converter->convert($markdown);
```

## Features

- Automatically wraps heading-based sections in `<section>` elements
- Respects heading hierarchy (h1, h2, h3, etc.)
- Creates proper nesting based on heading levels
- Works with any heading level
- No configuration required
- Integrates with semantic HTML practices

## How It Works

The extension processes the document after parsing and restructures it based on
heading levels:

1. **Detection**: Identifies all headings in the document
2. **Grouping**: Groups content following each heading
3. **Nesting**: Creates nested sections matching the heading hierarchy
4. **Wrapping**: Wraps each group in a `<section>` element

## Output Examples

### Simple Structure

Input:

```markdown
# Main Topic

Content here.

## Subtopic

More content.
```

Output:

```html

<section>
    <h1>Main Topic</h1>
    <p>Content here.</p>
    <section>
        <h2>Subtopic</h2>
        <p>More content.</p>
    </section>
</section>
```

### Complex Hierarchy

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

<section>
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
</section>
```

## Configuration

No configuration is required. Simply register the extension:

```php
$environment->addExtension(new ContentSlicerExtension());
```

## Advanced Usage

### Combining with HeadingLevel Extension

Adjust heading levels before creating sections:

```php
use Alto\CommonMark\Extension\HeadingLevel\HeadingLevelExtension;

$environment->addExtension(
    new HeadingLevelExtension(['down' => 1]) // Shift headings down by 1
);
$environment->addExtension(new ContentSlicerExtension());
```

### Styling Sections

Generate CSS to style the section structure:

```css
section {
  margin: 20px 0;
  padding: 16px;
  border-left: 4px solid #007BFF;
}

section section {
  margin-left: 20px;
  border-left-color: #28A745;
}

section section section {
  border-left-color: #FFC107;
}

h1, h2, h3, h4, h5, h6 {
  margin-top: 0;
}
```

### With Table of Contents

Generate a table of contents alongside sections:

```php
$html = $converter->convert($markdown);

// Extract heading levels and create TOC
preg_match_all('/<h([1-6]).*?>(.*?)<\/h\1>/s', $html, $matches);

$toc = '<ul>';
foreach ($matches[2] as $heading) {
    $toc .= '<li>' . strip_tags($heading) . '</li>';
}
$toc .= '</ul>';

echo $toc;
echo $html;
```

### Handling Content Before First Heading

Content that appears before the first heading is preserved at the document root
level:

Input:

```markdown
Introduction paragraph.

# Section 1

Content here.
```

Output:

```html
<p>Introduction paragraph.</p>
<section>
    <h1>Section 1</h1>
    <p>Content here.</p>
</section>
```

## Use Cases

### Documentation Sites

Structure documentation with automatic section wrapping for better semantics and
styling.

### Blog Posts

Automatically organize blog content with proper heading hierarchy.

### HTML Export

Create valid, nested section structures for better accessibility and semantic
meaning.

### API Documentation

Generate properly nested sections for endpoint documentation organized by
resource or category.

## Implementation Details

- **Pattern**: Event-based listener with custom node rendering
- **Event**: `DocumentParsedEvent`
- **Custom Nodes**: `Section` (custom block node)
- **Renderers**: `SectionRenderer` (renders `<section>` tags)

The extension listens to the document parsed event and creates custom `Section`
nodes that are then rendered as `<section>` HTML elements.

## Examples

### Book-like Structure

```markdown
# Part 1: Getting Started

## Chapter 1: Introduction

Content here.

## Chapter 2: Setup

More content.

# Part 2: Advanced Topics

## Chapter 3: Patterns

Advanced content.
```

### API Documentation

```markdown
# Users API

## User Objects

Description of user structure.

### Creating Users

POST endpoint details.

### Updating Users

PUT endpoint details.

## Authentication

Security information.
```

### Knowledge Base

```markdown
# HTML & CSS

## Formatting

### Text

Content about text formatting.

### Lists

Content about lists.

## Images

Image handling documentation.

# JavaScript

## Basics

JavaScript fundamentals.

## DOM

DOM manipulation guide.
```

## Accessibility Notes

The extension improves document accessibility by:

- Creating proper document outlines with nested sections
- Ensuring logical heading hierarchy
- Supporting assistive technologies with semantic HTML
- Enabling better document navigation for screen readers

## Troubleshooting

### Sections not appearing

Ensure you're registering the extension before creating the converter:

```php
$environment = new Environment();
$environment->addExtension(new ContentSlicerExtension());
$converter = new MarkdownConverter($environment);
```

### Unexpected nesting

Verify your heading hierarchy is logical. The extension respects heading levels
strictly. If you have an h1 followed by an h3 (skipping h2), the h3 will nest
under the h1.

To fix:

```markdown
# Main         <!-- h1 -->

## Sub         <!-- h2 - not h3 -->

### Sub-sub    <!-- h3 - now properly nested -->
```

### Combining with other extensions

Register ContentSlicer after parsing-related extensions:

```php
$environment->addExtension(new CommonMarkCoreExtension());
$environment->addExtension(new HeadingLevelExtension([...]));
$environment->addExtension(new ContentSlicerExtension());
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
