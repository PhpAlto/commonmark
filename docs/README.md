# Alto CommonMark Documentation

Nine `league/commonmark` extensions, each solving a specific documentation
problem cleanly.

## Extensions

| Extension       | Documentation                            |
|-----------------|------------------------------------------|
| CodeBlockTitle  | [CodeBlockTitle.md](CodeBlockTitle.md)   |
| ContentSlicer   | [ContentSlicer.md](ContentSlicer.md)     |
| HeadingLevel    | [HeadingLevel.md](HeadingLevel.md)       |
| Import          | [Import.md](Import.md)                   |
| Include         | [Include.md](Include.md)                 |
| LinkRewriter    | [LinkRewriter.md](LinkRewriter.md)       |
| Source          | [Source.md](Source.md)                   |
| TableOfContents | [TableOfContents.md](TableOfContents.md) |
| Tabs            | [Tabs.md](Tabs.md)                       |

## Choosing an Extension

| I want to…                            | Extension                             |
|---------------------------------------|---------------------------------------|
| Show filenames above code blocks      | [CodeBlockTitle](CodeBlockTitle.md)   |
| Wrap heading sections in `<section>`  | [ContentSlicer](ContentSlicer.md)     |
| Normalise heading levels              | [HeadingLevel](HeadingLevel.md)       |
| Embed a raw file or code snippet      | [Import](Import.md)                   |
| Include an external Markdown file     | [Include](Include.md)                 |
| Rewrite or prefix URLs                | [LinkRewriter](LinkRewriter.md)       |
| Display a source file with highlights | [Source](Source.md)                   |
| Auto-generate a table of contents     | [TableOfContents](TableOfContents.md) |
| Create tabbed content blocks          | [Tabs](Tabs.md)                       |

## Common Combinations

### Documentation site

```php
$environment->addExtension(new CodeBlockTitleExtension());
$environment->addExtension(new TableOfContentsExtension());
$environment->addExtension(new ContentSlicerExtension());
$environment->addExtension(new LinkRewriterExtension(['base_uri' => 'https://docs.example.com']));
```

### Multi-file content composition

```php
$environment->addExtension(new IncludeExtension(__DIR__ . '/content'));
$environment->addExtension(new HeadingLevelExtension(['down' => 1]));
$environment->addExtension(new ContentSlicerExtension());
```

### API or tutorial documentation

```php
$environment->addExtension(new SourceExtension(__DIR__));
$environment->addExtension(new CodeBlockTitleExtension());
$environment->addExtension(new TabsExtension());
```

---

[Main README](../README.md) · [league/commonmark](https://commonmark.thephpleague.com/) · [GitHub](https://github.com/PhpAlto/commonmark)
