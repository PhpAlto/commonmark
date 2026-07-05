# Tabs Extension

Creates accessible tabbed content with `@tabs` / `@tab` / `@endtabs` markers.
Generates WAI-ARIA compliant HTML with an embedded JavaScript tab-switcher.

## Basic Usage

```php
use Alto\CommonMark\Extension\Tabs\TabsExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;

$environment = new Environment();
$environment->addExtension(new TabsExtension());

$converter = new MarkdownConverter($environment);
```

```markdown
@tabs
@tab PHP
echo "Hello";
@tab JavaScript
console.log("Hello");
@endtabs
```

```html
<div class="tabs-container" data-tabs-id="tabs-…">
  <div class="tabs-list" role="tablist">
    <button class="tab active" role="tab" aria-selected="true" …>PHP</button>
    <button class="tab" role="tab" aria-selected="false" …>JavaScript</button>
  </div>
  <div class="tab-panels">
    <div class="tab-panel active" role="tabpanel" …><div>echo "Hello";</div></div>
    <div class="tab-panel" role="tabpanel" hidden …><div>console.log("Hello");</div></div>
  </div>
</div>
<script>…</script>
```

## Syntax

```markdown
@tabs
@tab Tab Title
Content for the first tab.
@tab "Tab with spaces"
Content for the second tab.
@endtabs
```

- `@tabs` opens a tab group; `@endtabs` closes it
- Each `@tab <title>` starts a new tab panel
- Titles may be unquoted (`@tab PHP`) or quoted (`@tab "PHP 8.3"`)
- Tab groups must be at column 0 (no indentation)

## Constructor

```php
new TabsExtension(array $config = [])
```

| Config key        | Type   | Default            | Description                                       |
|-------------------|--------|--------------------|---------------------------------------------------|
| `container_class` | string | `'tabs-container'` | CSS class on the outer `<div>`                    |
| `tabs_class`      | string | `'tabs-list'`      | CSS class on the tab buttons wrapper              |
| `tab_class`       | string | `'tab'`            | CSS class on each tab `<button>`                  |
| `panel_class`     | string | `'tab-panel'`      | CSS class on each panel `<div>`                   |
| `active_class`    | string | `'active'`         | Class added to the initially active tab and panel |
| `generate_ids`    | bool   | `true`             | Auto-generate IDs for ARIA wiring                 |

## HTML Structure

```html
<div class="tabs-container" data-tabs-id="tabs-{uniqueId}">

  <!-- Tab buttons -->
  <div class="tabs-list" role="tablist">
    <button class="tab active"
            id="tabs-{id}-tab-0"
            role="tab"
            aria-selected="true"
            aria-controls="tabs-{id}-panel-0"
            data-tab-index="0">
      First Tab
    </button>
    <button class="tab"
            id="tabs-{id}-tab-1"
            role="tab"
            aria-selected="false"
            aria-controls="tabs-{id}-panel-1"
            data-tab-index="1">
      Second Tab
    </button>
  </div>

  <!-- Tab panels -->
  <div class="tab-panels">
    <div class="tab-panel active"
         id="tabs-{id}-panel-0"
         role="tabpanel"
         aria-labelledby="tabs-{id}-tab-0"
         data-panel-index="0">
      <div>First tab content</div>
    </div>
    <div class="tab-panel"
         id="tabs-{id}-panel-1"
         role="tabpanel"
         aria-labelledby="tabs-{id}-tab-1"
         data-panel-index="1"
         hidden>
      <div>Second tab content</div>
    </div>
  </div>
</div>

<script>/* tab switcher for this group */</script>
```

Each tab group receives a unique `data-tabs-id` (via `uniqid()`). The embedded
`<script>` handles click events: it deactivates all tabs/panels and activates
the clicked one, updating `aria-selected` and the `hidden` attribute.

## Accessibility

The extension generates full WAI-ARIA markup:

- `role="tablist"` on the button container
- `role="tab"` on each button with `aria-selected`
- `role="tabpanel"` on each panel with `aria-labelledby`
- `hidden` attribute on non-active panels
- Keyboard navigation handled via the embedded script

## Styling

```css
.tabs-container { border: 1px solid #ddd; border-radius: 4px; }
.tabs-list { display: flex; gap: 4px; padding: 8px; background: #f5f5f5; }
.tab { padding: 6px 14px; border: none; background: none; cursor: pointer; }
.tab.active { background: white; border-radius: 3px; font-weight: bold; }
.tab-panel { padding: 16px; }
.tab-panel[hidden] { display: none; }
```

## See Also

- [ContentSlicer](ContentSlicer.md): wrap heading sections in semantic
  `<section>` tags

---

> **This package is part of
the [alto/commonmark](https://github.com/PhpAlto/commonmark) monorepo.**  
> This repository is a read-only split. To file issues, open pull requests, or contribute, use the main repository: **https://github.com/PhpAlto/commonmark**
