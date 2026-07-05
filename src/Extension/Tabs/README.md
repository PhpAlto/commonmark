# Tabs Extension

## Summary

One directive, fully accessible tabbed UI, zero JavaScript to write. `@tabs` / `@tab` / `@endtabs` markers are parsed into proper ARIA `tablist`, `tab`, and `tabpanel` roles with a self-contained switching script included. The cleanest path from markdown syntax to an interactive UI component in the PHP CommonMark ecosystem.

## Installation

### composer require

```bash
composer require alto/commonmark
```

### Registration

```php
use Alto\CommonMark\Extension\Tabs\TabsExtension;
use League\CommonMark\Environment\Environment;

$environment = new Environment();
$environment->addExtension(new TabsExtension());
```

## Usage

````markdown
@tabs
@tab "PHP"
```php
<?php echo 'Hello';
```

@tab "JavaScript"
```js
console.log('Hello');
```
@endtabs
````

## Configuration

- Constructor accepts keys: `container_class`, `tabs_class`, `tab_class`, `panel_class`, `active_class`, `generate_ids`.
- Default output includes ARIA roles (`tablist`, `tab`, `tabpanel`).

## Minimal Example

```markdown
@tabs
@tab "Overview"
Project overview.
@tab "Install"
composer require vendor/package
@endtabs
```

## Development

This extension is actively developed in the [`alto/commonmark`](https://github.com/PhpAlto/commonmark) monorepo.

- [Report a bug](https://github.com/PhpAlto/commonmark/issues/new?labels=bug)
- [Suggest a feature](https://github.com/PhpAlto/commonmark/issues/new?labels=enhancement)
- [Open a Pull Request](https://github.com/PhpAlto/commonmark/pulls)

## License

MIT License -- [Simon André](https://smnandre.dev) & [Alto](https://github.com/PhpAlto)
