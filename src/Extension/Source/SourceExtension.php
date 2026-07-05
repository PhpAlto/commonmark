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

namespace Alto\CommonMark\Extension\Source;

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

final readonly class SourceExtension implements ExtensionInterface
{
    private string $basePath;

    /** @var list<string> */
    private array $allowedExtensions;

    private bool $escapeHtml;
    private int $maxFileSize;

    /**
     * @param list<string> $allowedExtensions
     */
    public function __construct(
        string $basePath = '.',
        array $allowedExtensions = [],
        bool $escapeHtml = true,
        int $maxFileSize = 1048576,
    ) {
        $this->basePath = rtrim($basePath, '/');
        $this->allowedExtensions = $allowedExtensions;
        $this->escapeHtml = $escapeHtml;
        $this->maxFileSize = $maxFileSize;
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addBlockStartParser(new SourceBlockParser(
                $this->basePath,
                $this->allowedExtensions,
                $this->maxFileSize
            ), 200)
            ->addRenderer(SourceBlock::class, new SourceRenderer($this->escapeHtml));
    }
}

final class SourceBlock extends AbstractBlock
{
    /**
     * @param array<string, int>|null $lines
     */
    public function __construct(
        public readonly string $path,
        public readonly string $content,
        public readonly ?string $language,
        public readonly ?string $title,
        public readonly ?array $lines,
        public readonly bool $showLineNumbers,
        public readonly ?string $highlight,
        public readonly ?string $error = null,
    ) {
        parent::__construct();
    }
}

final readonly class SourceBlockParser implements BlockStartParserInterface
{
    /** @var list<string> */
    private array $allowedExtensions;

    private int $maxFileSize;

    private string $basePath;

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

        if (!str_starts_with($line, '@source ')) {
            return BlockStart::none();
        }

        $cursor->advanceToEnd();

        if (!preg_match('/^@source\s+"([^"]+)"(?:\s+\{([^}]+)\})?$/', $line, $matches)) {
            $block = new SourceBlock('', '', null, null, null, false, null, 'Invalid source syntax');

            return BlockStart::of(new SourceBlockContinueParser($block));
        }

        $path = $matches[1];
        /**
         * @var array<string, mixed> $options
         */
        $options = $this->parseOptions($matches[2] ?? '');

        // Validate file extension if restrictions are set
        if (!empty($this->allowedExtensions)) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            if (!in_array($extension, $this->allowedExtensions, true)) {
                $block = new SourceBlock(
                    $path, '', null, null, null, false, null,
                    "File type not allowed: .$extension"
                );

                return BlockStart::of(new SourceBlockContinueParser($block));
            }
        }

        $content = $this->loadRawFile($path, $options);

        /** @var string $contentStr */
        $contentStr = $content['content'] ?? '';
        /** @var string|null $language */
        $language = $options['lang'] ?? $this->detectLanguage($path);
        /** @var string|null $title */
        $title = $options['title'] ?? null;
        /** @var array<string, int>|null $lines */
        $lines = $options['lines'] ?? null;
        /** @var bool $showNumbers */
        $showNumbers = $options['numbers'] ?? false;
        /** @var string|null $highlight */
        $highlight = $options['highlight'] ?? null;
        /** @var string|null $error */
        $error = $content['error'] ?? null;

        $block = new SourceBlock(
            $path,
            $contentStr,
            $language,
            $title,
            $lines,
            $showNumbers,
            $highlight,
            $error
        );

        return BlockStart::of(new SourceBlockContinueParser($block));
    }

    /**
     * @return array<string, int>|null
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
     * @return array<string, mixed>
     */
    private function parseOptions(string $optionsStr): array
    {
        /**
         * @var array<string, mixed> $options
         */
        $options = [];

        if (empty($optionsStr)) {
            return $options;
        }

        foreach (preg_split('/,\s*/', $optionsStr) ?: [] as $pair) {
            if (preg_match('/^(\w+):\s*(.+)$/', trim($pair), $matches)) {
                $key = $matches[1];
                $value = trim($matches[2], '"\'');

                $options[$key] = match ($key) {
                    'lines' => $this->parseLineRange($value),
                    'numbers' => 'true' === $value,
                    'highlight' => $value,
                    default => $value,
                };
            }
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, string|array<string, int>>
     */
    private function loadRawFile(string $path, array $options): array
    {
        $fullPath = $this->resolvePath($path);

        if ('' === $fullPath) {
            return ['error' => "Path not allowed: $path"];
        }

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
        if (isset($options['lines']) && is_array($options['lines']) && !empty($options['lines'])) {
            $lines = explode("\n", $content);
            /** @var int $startLine */
            $startLine = $options['lines']['start'];
            /** @var int $endLine */
            $endLine = $options['lines']['end'];
            $start = max(1, $startLine) - 1;
            $end = min(count($lines), $endLine);

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

    private function detectLanguage(string $path): ?string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml' => 'php',
            'js', 'javascript', 'mjs' => 'javascript',
            'ts', 'typescript' => 'typescript',
            'jsx' => 'jsx',
            'tsx' => 'tsx',
            'py', 'python', 'py3' => 'python',
            'rb', 'ruby' => 'ruby',
            'go' => 'go',
            'rs', 'rust' => 'rust',
            'java' => 'java',
            'c' => 'c',
            'cpp', 'cc', 'cxx', 'c++' => 'cpp',
            'cs', 'csharp' => 'csharp',
            'swift' => 'swift',
            'kt', 'kotlin' => 'kotlin',
            'scala' => 'scala',
            'r' => 'r',
            'sh', 'bash', 'zsh' => 'bash',
            'ps1', 'powershell' => 'powershell',
            'sql' => 'sql',
            'html', 'htm' => 'html',
            'xml', 'xhtml', 'xsl' => 'xml',
            'css' => 'css',
            'scss', 'sass' => 'scss',
            'less' => 'less',
            'json' => 'json',
            'yaml', 'yml' => 'yaml',
            'toml' => 'toml',
            'ini', 'cfg', 'conf' => 'ini',
            'md', 'markdown' => 'markdown',
            'rst' => 'restructuredtext',
            'tex', 'latex' => 'latex',
            'dockerfile' => 'dockerfile',
            'makefile', 'mk' => 'makefile',
            'cmake' => 'cmake',
            'nginx' => 'nginx',
            'apache', 'htaccess' => 'apache',
            'vim' => 'vim',
            'lua' => 'lua',
            'perl', 'pl' => 'perl',
            'asm', 's' => 'assembly',
            'diff', 'patch' => 'diff',
            'csv' => 'csv',
            'txt', 'text' => 'text',
            default => null,
        };
    }
}

final class SourceBlockContinueParser extends AbstractBlockContinueParser
{
    private readonly SourceBlock $block;

    public function __construct(SourceBlock $block)
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

final readonly class SourceRenderer implements NodeRendererInterface
{
    private bool $escapeHtml;

    public function __construct(bool $escapeHtml)
    {
        $this->escapeHtml = $escapeHtml;
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        assert($node instanceof SourceBlock);

        if ($node->error) {
            return sprintf(
                '<div class="source-error">Source error: %s</div>',
                htmlspecialchars($node->error)
            );
        }

        $html = '<div class="source-block">';

        // Add title if provided
        if ($node->title) {
            $html .= sprintf(
                '<div class="source-title">%s</div>',
                htmlspecialchars($node->title)
            );
        }

        // Add file path
        $html .= sprintf(
            '<div class="source-path">%s</div>',
            htmlspecialchars($node->path)
        );

        // Prepare content
        $content = $this->escapeHtml ? htmlspecialchars($node->content) : $node->content;

        // Add line numbers if requested
        if ($node->showLineNumbers) {
            $lines = explode("\n", $content);
            /** @var int $startLine */
            $startLine = $node->lines['start'] ?? 1;
            /**
             * @var array<string> $numberedLines
             */
            $numberedLines = [];

            foreach ($lines as $index => $line) {
                $lineNumber = $startLine + $index;
                $highlighted = $this->isHighlighted($lineNumber, $node->highlight);
                $class = $highlighted ? ' class="highlighted"' : '';

                $numberedLines[] = sprintf(
                    '<span class="line"%s><span class="line-number">%d</span>%s</span>',
                    $class,
                    $lineNumber,
                    $line
                );
            }

            $content = implode("\n", $numberedLines);
        } elseif ($node->highlight) {
            // Highlight specific lines without numbers
            $lines = explode("\n", $content);
            /**
             * @var array<string> $highlightedLines
             */
            $highlightedLines = [];

            foreach ($lines as $index => $line) {
                /** @var int $baseLineNum */
                $baseLineNum = $node->lines['start'] ?? 1;
                $lineNumber = $baseLineNum + $index;
                $highlighted = $this->isHighlighted($lineNumber, $node->highlight);

                if ($highlighted) {
                    $highlightedLines[] = sprintf(
                        '<span class="line highlighted">%s</span>',
                        $line
                    );
                } else {
                    $highlightedLines[] = $line;
                }
            }

            $content = implode("\n", $highlightedLines);
        }

        // Create the code block
        if ($node->language) {
            $html .= sprintf(
                '<pre><code class="language-%s">%s</code></pre>',
                htmlspecialchars($node->language),
                $content
            );
        } else {
            $html .= sprintf(
                '<pre><code>%s</code></pre>',
                $content
            );
        }

        $html .= '</div>';

        return $html;
    }

    private function isHighlighted(int $lineNumber, ?string $highlight): bool
    {
        if (!$highlight) {
            return false;
        }

        // Parse highlight ranges: "1,3-5,7"
        $ranges = explode(',', $highlight);

        foreach ($ranges as $range) {
            $range = trim($range);

            if (preg_match('/^(\d+)-(\d+)$/', $range, $matches)) {
                $start = (int) $matches[1];
                $end = (int) $matches[2];

                if ($lineNumber >= $start && $lineNumber <= $end) {
                    return true;
                }
            } elseif (preg_match('/^(\d+)$/', $range, $matches)) {
                if ($lineNumber === (int) $matches[1]) {
                    return true;
                }
            }
        }

        return false;
    }
}
