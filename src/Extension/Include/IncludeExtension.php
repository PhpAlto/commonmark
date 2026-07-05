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

namespace Alto\CommonMark\Extension\Include;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Environment\EnvironmentInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final readonly class IncludeExtension implements ExtensionInterface
{
    private string $basePath;

    /** @var list<string> */
    private array $allowedExtensions;

    private int $maxDepth;

    private int $maxFileSize;

    /**
     * @param list<string> $allowedExtensions
     */
    public function __construct(
        string $basePath = '.',
        int $maxDepth = 10,
        array $allowedExtensions = ['md', 'markdown'],
        int $maxFileSize = 1048576,
    ) {
        $this->basePath = rtrim($basePath, '/');
        $this->maxDepth = $maxDepth;
        $this->allowedExtensions = $allowedExtensions;
        $this->maxFileSize = $maxFileSize;
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addEventListener(
                \League\CommonMark\Event\DocumentParsedEvent::class,
                new IncludeProcessor($this->maxDepth, $environment)
            )
            ->addBlockStartParser(
                new IncludeBlockParser(
                    $this->basePath,
                    $this->allowedExtensions,
                    $this->maxFileSize
                ),
                200
            )
            ->addRenderer(IncludeBlock::class, new IncludeRenderer());
    }
}

final class IncludeBlock extends AbstractBlock
{
    /**
     * @param array{start: int, end: int}|null $lines
     */
    public function __construct(
        public readonly string $path,
        public readonly ?array $lines,
        public readonly ?string $error = null,
    ) {
        parent::__construct();
        $this->rawContent = '';
    }

    private string $rawContent;

    public function setRawContent(string $content): void
    {
        $this->rawContent = $content;
    }

    public function getRawContent(): string
    {
        return $this->rawContent;
    }
}

/**
 * @phpstan-type IncludeLineRange array{start: int, end: int}
 * @phpstan-type IncludeOptions array{lines?: IncludeLineRange}
 * @phpstan-type IncludeLoadResult array{content?: string, error?: string}
 */
final readonly class IncludeBlockParser implements BlockStartParserInterface
{
    private string $basePath;

    /** @var list<string> */
    private array $allowedExtensions;

    private int $maxFileSize;

    /**
     * @param list<string> $allowedExtensions
     */
    public function __construct(
        string $basePath,
        array $allowedExtensions,
        int $maxFileSize,
    ) {
        $this->basePath = $basePath;
        $this->allowedExtensions = $allowedExtensions;
        $this->maxFileSize = $maxFileSize;
    }

    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        $indent = $cursor->getIndent();

        if ($indent > 3) {
            return BlockStart::none();
        }

        $line = trim($cursor->getRemainder());

        if (!str_starts_with($line, '@include ')) {
            return BlockStart::none();
        }

        $cursor->advanceToEnd();

        if (!preg_match('/^@include\s+"([^"]+)"(?:\s+\{([^}]+)\})?$/', $line, $matches)) {
            $block = new IncludeBlock('', null, 'Invalid include syntax');

            return BlockStart::of(new IncludeBlockContinueParser($block));
        }

        $path = $matches[1];
        $options = $this->parseOptions($matches[2] ?? '');

        // Validate extension
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (!in_array($extension, $this->allowedExtensions, true)) {
            $block = new IncludeBlock($path, null, "File type not allowed: .$extension");

            return BlockStart::of(new IncludeBlockContinueParser($block));
        }

        // Load content (parsing happens in processor)
        $content = $this->loadFile($path, $options);
        $lineRange = $options['lines'] ?? null;

        $block = new IncludeBlock(
            $path,
            $lineRange,
            $content['error'] ?? null
        );
        $block->setRawContent($content['content'] ?? '');

        return BlockStart::of(new IncludeBlockContinueParser($block));
    }

    /**
     * @return IncludeLineRange|null
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

    /**
     * @return IncludeOptions
     */
    private function parseOptions(string $optionsStr): array
    {
        /** @var IncludeOptions $options */
        $options = [];

        if ('' === $optionsStr) {
            return $options;
        }

        foreach (preg_split('/,\s*/', $optionsStr) ?: [] as $pair) {
            if (!preg_match('/^(\w+):\s*(.+)$/', trim($pair), $matches)) {
                continue;
            }

            $key = $matches[1];
            $value = trim($matches[2], '"\'');
            if ('lines' === $key) {
                $lineRange = $this->parseLineRange($value);
                if (null !== $lineRange) {
                    $options['lines'] = $lineRange;
                }
            }
        }

        return $options;
    }

    /**
     * @param IncludeOptions $options
     *
     * @return IncludeLoadResult
     */
    private function loadFile(string $path, array $options): array
    {
        $fullPath = $this->resolvePath($path);

        if ('' === $fullPath) {
            return ['error' => "Path not allowed: $path"];
        }

        // Validation
        if (!file_exists($fullPath)) {
            return ['error' => "File not found: $path"];
        }

        if (!is_readable($fullPath)) {
            return ['error' => "File not readable: $path"];
        }

        $fileSize = filesize($fullPath);
        if (false === $fileSize || $fileSize > $this->maxFileSize) {
            /** @var int<0, max> $fileSizeVal */
            $fileSizeVal = $fileSize ?: 0;
            $sizeMB = round($fileSizeVal / 1048576, 2);
            $maxMB = round($this->maxFileSize / 1048576, 2);

            return ['error' => "File too large: {$sizeMB}MB (max: {$maxMB}MB)"];
        }

        $content = file_get_contents($fullPath);

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

        return ['content' => $content];
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
}

final class IncludeBlockContinueParser extends AbstractBlockContinueParser
{
    private readonly IncludeBlock $block;

    public function __construct(IncludeBlock $block)
    {
        $this->block = $block;
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

final readonly class IncludeRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        assert($node instanceof IncludeBlock);

        if ($node->error) {
            return sprintf(
                '<div class="include-error">Include error: %s</div>',
                htmlspecialchars($node->error)
            );
        }

        // The content should have been parsed and added as children by the processor
        // Just render the children
        return $childRenderer->renderNodes($node->children());
    }
}

final class IncludeProcessor
{
    private readonly int $maxDepth;
    private readonly EnvironmentBuilderInterface $environment;
    private int $currentDepth = 0;

    public function __construct(int $maxDepth, EnvironmentBuilderInterface $environment)
    {
        $this->maxDepth = $maxDepth;
        $this->environment = $environment;
    }

    public function __invoke(\League\CommonMark\Event\DocumentParsedEvent $event): void
    {
        $document = $event->getDocument();
        $walker = $document->walker();

        while ($event = $walker->next()) {
            $node = $event->getNode();

            if ($node instanceof IncludeBlock && $event->isEntering()) {
                if (null !== $node->error) {
                    continue;
                }

                $content = $node->getRawContent();
                if (empty($content)) {
                    continue;
                }

                ++$this->currentDepth;
                $parsedContent = $this->parseMarkdown($content);
                --$this->currentDepth;

                if (null !== $parsedContent) {
                    foreach ($parsedContent->children() as $child) {
                        $node->appendChild($child);
                    }
                }
            }
        }
    }

    private function parseMarkdown(string $markdown): ?\League\CommonMark\Node\Block\Document
    {
        if ($this->currentDepth > $this->maxDepth) {
            return null;
        }

        if (!$this->environment instanceof EnvironmentInterface) {
            return null;
        }

        try {
            $parser = new MarkdownParser($this->environment);

            return $parser->parse($markdown);
        } catch (\Throwable) {
            return null;
        }
    }
}
