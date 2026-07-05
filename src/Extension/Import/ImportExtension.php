<?php

declare(strict_types=1);

/*
 * This file is part of the ALTO Commonmark package.
 *
 * © 2025-Present Simon André
 *
 * For full copyright and license information, please see
 * the LICENSE file distributed with this source code.
 */

namespace Alto\CommonMark\Extension\Import;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class ImportExtension implements ExtensionInterface
{
    private string $basePath;

    /** @var list<string> */
    private array $importedFiles = [];
    private int $maxDepth;

    public function __construct(string $basePath = '.', int $maxDepth = 10)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->maxDepth = $maxDepth;
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addBlockStartParser(new ImportParser($this->basePath, $this->importedFiles, $this->maxDepth), 100)
            ->addRenderer(ImportBlock::class, new ImportRenderer());
    }
}

final class ImportBlock extends AbstractBlock
{
    /**
     * @param array{start: int, end: int}|null $lines
     */
    public function __construct(
        public readonly string $path,
        public readonly ?array $lines,
        public readonly ?string $language,
        public readonly int $indent,
        public readonly string $content,
        public readonly ?string $error = null,
    ) {
        parent::__construct();
    }
}

/**
 * @phpstan-type ImportLineRange array{start: int, end: int}
 * @phpstan-type ImportOptions array{
 *     lines?: ImportLineRange,
 *     lang?: string,
 *     indent?: int
 * }
 * @phpstan-type ImportLoadResult array{content?: string, error?: string}
 */
final class ImportParser implements BlockStartParserInterface
{
    private int $currentDepth = 0;

    /**
     * @param list<string> $importedFiles
     */
    public function __construct(
        private string $basePath,
        private array &$importedFiles,
        private int $maxDepth,
    ) {
    }

    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        $indent = $cursor->getIndent();

        if ($indent > 3) {
            return BlockStart::none();
        }

        $line = trim($cursor->getRemainder());

        if (!str_starts_with($line, '@import ')) {
            return BlockStart::none();
        }

        $cursor->advanceToEnd();

        // Parse @import "file.md" {lines: 1-10, lang: php, indent: 2}
        if (!preg_match('/^@import\s+"([^"]+)"(?:\s+\{([^}]+)\})?$/', $line, $matches)) {
            $block = new ImportBlock('', null, null, 0, '', 'Invalid import syntax');

            return BlockStart::of(new ImportBlockParser($block));
        }

        $path = $matches[1];
        $options = $this->parseOptions($matches[2] ?? '');

        // Prevent infinite recursion
        if ($this->currentDepth >= $this->maxDepth) {
            $block = new ImportBlock($path, null, null, 0, '', 'Max import depth exceeded');

            return BlockStart::of(new ImportBlockParser($block));
        }

        // Prevent path traversal
        $fullPath = $this->resolvePath($path);
        if ('' === $fullPath) {
            $block = new ImportBlock($path, null, null, 0, '', "Path not allowed: $path");

            return BlockStart::of(new ImportBlockParser($block));
        }

        // Prevent circular imports
        if (in_array($fullPath, $this->importedFiles, true)) {
            $block = new ImportBlock($path, null, null, 0, '', 'Circular import detected');

            return BlockStart::of(new ImportBlockParser($block));
        }

        $this->importedFiles[] = $fullPath;
        ++$this->currentDepth;

        $content = $this->loadFile($fullPath, $options);

        --$this->currentDepth;

        $lineRange = $options['lines'] ?? null;
        $language = $options['lang'] ?? null;
        $blockIndent = $options['indent'] ?? $indent;

        $block = new ImportBlock(
            $path,
            $lineRange,
            $language,
            $blockIndent,
            $content['content'] ?? '',
            $content['error'] ?? null
        );

        return BlockStart::of(new ImportBlockParser($block));
    }

    /**
     * @return ImportOptions
     */
    private function parseOptions(string $optionsStr): array
    {
        /** @var ImportOptions $options */
        $options = [];

        if ('' === $optionsStr) {
            return $options;
        }

        foreach (preg_split('/,\s*/', $optionsStr) ?: [] as $pair) {
            if (!preg_match('/^(\w+):\s*(.+)$/', trim($pair), $matches)) {
                continue;
            }

            $key = $matches[1];
            $value = trim($matches[2]);

            switch ($key) {
                case 'lines':
                    $lineRange = $this->parseLineRange($value);
                    if (null !== $lineRange) {
                        $options['lines'] = $lineRange;
                    }

                    break;
                case 'indent':
                    $options['indent'] = (int) $value;

                    break;
                case 'lang':
                    $options['lang'] = $value;
            }
        }

        return $options;
    }

    /**
     * @return ImportLineRange|null
     */
    private function parseLineRange(string $range): ?array
    {
        if (preg_match('/^(\d+)-(\d+)$/', $range, $matches)) {
            return [
                'start' => (int) $matches[1],
                'end' => (int) $matches[2],
            ];
        }

        if (preg_match('/^(\d+)$/', $range, $matches)) {
            $line = (int) $matches[1];

            return [
                'start' => $line,
                'end' => $line,
            ];
        }

        return null;
    }

    private function resolvePath(string $path): string
    {
        $realBase = realpath($this->basePath);
        if (false === $realBase) {
            return '';
        }

        $candidate = str_starts_with($path, '/')
            ? $realBase.$path
            : $realBase.'/'.$path;

        $parts = explode('/', $candidate);
        $resolved = [];
        foreach ($parts as $part) {
            if ('' === $part || '.' === $part) {
                continue;
            }
            if ('..' === $part) {
                array_pop($resolved);
            } else {
                $resolved[] = $part;
            }
        }
        $resolvedPath = '/'.implode('/', $resolved);

        if (!str_starts_with($resolvedPath, $realBase.'/') && $resolvedPath !== $realBase) {
            return '';
        }

        return $resolvedPath;
    }

    /**
     * @param ImportOptions $options
     *
     * @return ImportLoadResult
     */
    private function loadFile(string $path, array $options): array
    {
        if (!file_exists($path)) {
            return ['error' => "File not found: $path"];
        }

        if (!is_readable($path)) {
            return ['error' => "File not readable: $path"];
        }

        $content = file_get_contents($path);

        if (false === $content) {
            return ['error' => "Failed to read file: $path"];
        }

        // Apply line range if specified
        if (isset($options['lines'])) {
            $lines = explode("\n", $content);
            $start = max(1, $options['lines']['start']) - 1;
            $end = min(count($lines), $options['lines']['end']);

            $content = implode("\n", array_slice($lines, $start, $end - $start));
        }

        // Apply indentation if specified
        if (isset($options['indent']) && $options['indent'] > 0) {
            $indentation = str_repeat(' ', $options['indent']);
            $lines = explode("\n", $content);
            $content = implode("\n", array_map(fn (string $line): string => $indentation.$line, $lines));
        }

        return ['content' => $content];
    }
}

final class ImportBlockParser extends AbstractBlockContinueParser
{
    public function __construct(
        private ImportBlock $block,
    ) {
    }

    public function getBlock(): AbstractBlock
    {
        return $this->block;
    }

    public function tryContinue(
        Cursor $cursor,
        BlockContinueParserInterface $activeBlockParser,
    ): ?BlockContinue {
        return BlockContinue::none();
    }
}

final readonly class ImportRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        assert($node instanceof ImportBlock);

        if ($node->error) {
            return sprintf(
                '<div class="import-error">Import error: %s</div>',
                htmlspecialchars($node->error)
            );
        }

        if ($node->language) {
            return sprintf(
                '<pre><code class="language-%s">%s</code></pre>',
                htmlspecialchars($node->language),
                htmlspecialchars($node->content)
            );
        }

        // Return raw content to be parsed as markdown
        return $node->content;
    }
}
