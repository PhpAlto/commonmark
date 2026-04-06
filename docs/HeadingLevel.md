# HeadingLevel Extension

Adjusts heading levels in a document through mapping, shifts, or custom
callbacks.

## Basic Usage

```php
use Alto\CommonMark\Extension\HeadingLevel\HeadingLevelExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;

$environment = new Environment();
// Shift all headings down by 1 level (h1 → h2, h2 → h3, etc.)
$environment->addExtension(new HeadingLevelExtension(['down' => 1]));

$converter = new MarkdownConverter($environment);

$markdown = "# Main Title\n## Subtitle";
echo $converter->convert($markdown);
```

## Features

- **Shift headings**: Uniformly increase or decrease heading levels
- **Map headings**: Explicitly define h1→h3, h2→h4, etc.
- **Custom logic**: Use callbacks for complex transformation
- **Flexible configuration**: Multiple strategies in one config
- **Validation**: Prevents invalid heading levels (h0, h7+)

## Configuration Options

### 1. Simple Shift (down/up)

Shift all headings by a specific amount:

```php
// Shift down by 1 level
new HeadingLevelExtension(['down' => 1])
// h1 → h2, h2 → h3, ...

// Shift down by 2 levels
new HeadingLevelExtension(['down' => 2])
// h1 → h3, h2 → h4, ...

// Shift up by 1 level (negative down)
new HeadingLevelExtension(['down' => -1])
// h2 → h1, h3 → h2, ...
```

### 2. Level Mapping

Explicitly map heading levels:

```php
new HeadingLevelExtension([
    'map' => [
        1 => 2,  // h1 → h2
        2 => 3,  // h2 → h3
        3 => 3,  // h3 stays h3
    ]
])
```

### 3. Custom Callback

Use a function for complex logic:

```php
new HeadingLevelExtension([
    'callback' => function(int $level): int {
        // Custom logic
        return min($level + 1, 6);  // Shift down but cap at h6
    }
])
```

### 4. Combined Configuration

Mix multiple strategies (applied in order):

```php
new HeadingLevelExtension([
    'down' => 1,            // First: shift down by 1
    'map' => [1 => 2],      // Then: map h1 to h2 (now effective h2 → h2)
    'callback' => function($level) {
        return min($level, 6);  // Ensure max h6
    }
])
```

## Output Examples

### Shift Down

Input:

```markdown
# Main Title

## Subtitle

### Details
```

Config: `['down' => 1]`

Output:

```html
<h2>Main Title</h2>
<h3>Subtitle</h3>
<h4>Details</h4>
```

### Shift Up

Input:

```markdown
### Nested Heading

#### Sub-section
```

Config: `['down' => -2]`

Output:

```html
<h1>Nested Heading</h1>
<h2>Sub-section</h2>
```

### Custom Mapping

Input:

```markdown
# Title

## Section

### Subsection
```

Config: `['map' => [1 => 2, 2 => 3, 3 => 4]]`

Output:

```html
<h2>Title</h2>
<h3>Section</h3>
<h4>Subsection</h4>
```

## Advanced Usage

### Normalizing Imported Content

When embedding content from external sources:

```php
// External content starts at h1, but you need h2
$environment->addExtension(
    new HeadingLevelExtension(['down' => 1])
);
```

### Preventing Invalid Levels

Ensure headings don't exceed h6:

```php
new HeadingLevelExtension([
    'callback' => function(int $level): int {
        return min($level + 2, 6);  // Max h6
    }
])
```

### Document Composition

Shift content based on context:

```php
function convertFragment(string $markdown, int $baseLevel): string {
    $env = new Environment();
    $env->addExtension(
        new HeadingLevelExtension([
            'down' => $baseLevel - 1
        ])
    );
    
    $converter = new MarkdownConverter($env);
    return $converter->convert($markdown);
}

// Use different base levels
echo convertFragment($intro, 1);      // Starts at h1
echo convertFragment($section1, 2);   // Starts at h2
echo convertFragment($section2, 2);   // Starts at h2
```

### With ContentSlicer

Adjust levels before creating sections:

```php
$environment->addExtension(
    new HeadingLevelExtension(['down' => 1])
);
$environment->addExtension(
    new ContentSlicerExtension()
);
```

The heading adjustment happens before sectioning, so sections reflect the
adjusted levels.

## Common Patterns

### Include Fragment in Document

```php
// Main document starts with h1
$main = "# Main Document\n## Introduction";

// Fragment to include at h3 level
$fragment = "# Fragment Title\n## Subsection";

$env = new Environment();
$env->addExtension(
    new HeadingLevelExtension(['down' => 2])  // Shift fragment to h3, h4
);

$converter = new MarkdownConverter($env);

echo "# Document\n" . $fragment;  // Fragment adjusted to fit
```

### Multi-level Document Structure

```php
$config = [
    'level1' => ['down' => 0],    // h1, h2, h3...
    'level2' => ['down' => 1],    // h2, h3, h4...
    'level3' => ['down' => 2],    // h3, h4, h5...
];

function includeMarkdown(string $file, int $level): string {
    $env = new Environment();
    $env->addExtension(
        new HeadingLevelExtension($config["level$level"])
    );
    $converter = new MarkdownConverter($env);
    return $converter->convert(file_get_contents($file));
}
```

### Limiting Maximum Heading Level

Ensure no heading exceeds h6:

```php
new HeadingLevelExtension([
    'callback' => function(int $level): int {
        // Shift but cap at h6
        return min($level + 1, 6);
    }
])
```

## Implementation Details

- **Pattern**: Event-based processor
- **Event**: `DocumentParsedEvent`
- **Custom Nodes**: None (modifies existing `Heading` nodes)
- **Tree Traversal**: Walks AST and modifies heading levels

The extension processes the document after parsing and directly modifies heading
levels in the AST.

## Examples

### Book Structure with Included Chapters

```php
// chapters/intro.md starts with # Introduction
// chapters/chapter1.md starts with # Chapter 1
// chapters/chapter2.md starts with # Chapter 2

$env = new Environment();
$env->addExtension(new ContentSlicerExtension());

$book = "# My Book\n\n";
$book .= $convertFragment('chapters/intro.md', 2);
$book .= $convertFragment('chapters/chapter1.md', 2);
$book .= $convertFragment('chapters/chapter2.md', 2);

$converter = new MarkdownConverter($env);
echo $converter->convert($book);
```

### API Documentation

```php
// Each endpoint documentation starts with # Title
// Adjust to h3 under # API section

$env = new Environment();
$env->addExtension(
    new HeadingLevelExtension(['down' => 2])
);

$markdown = "# API\n\n" . file_get_contents('endpoints.md');
$converter = new MarkdownConverter($env);
echo $converter->convert($markdown);
```

## Troubleshooting

### Headings not changing

Ensure the extension is registered before creating the converter:

```php
$environment = new Environment();
$environment->addExtension(
    new HeadingLevelExtension(['down' => 1])
);
$converter = new MarkdownConverter($environment);  // Must be after
```

### Invalid heading levels created

Check your configuration doesn't create h0 or h7+:

```php
// Bad: creates h0
new HeadingLevelExtension(['down' => -1])  // h1 → h0

// Good: validate levels
new HeadingLevelExtension([
    'callback' => function(int $level): int {
        return max(1, min($level - 1, 6));  // Keep within h1-h6
    }
])
```

### Mapping not applied as expected

Remember that multiple config options apply in sequence:

```php
[
    'down' => 1,
    'map' => [2 => 3]
]
// First applies down, then map
// So h1 → h2, then h2 → h3 via map
```

## See Also

- [ContentSlicer Extension](ContentSlicer.md) - Create sections based on heading
  levels
- [league/commonmark documentation](https://commonmark.thephpleague.com/)

---

> **This package is part of
the [alto/commonmark](https://github.com/PhpAlto/commonmark) monorepo.**  
> This repository is a read-only split — to file issues, open pull requests, or
> contribute, please use the main repository: *
*https://github.com/PhpAlto/commonmark**
