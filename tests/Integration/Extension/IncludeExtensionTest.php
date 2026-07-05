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

namespace Alto\CommonMark\Tests\Integration\Extension;

use Alto\CommonMark\Extension\Include\IncludeBlock;
use Alto\CommonMark\Extension\Include\IncludeBlockContinueParser;
use Alto\CommonMark\Extension\Include\IncludeBlockParser;
use Alto\CommonMark\Extension\Include\IncludeExtension;
use Alto\CommonMark\Extension\Include\IncludeProcessor;
use Alto\CommonMark\Extension\Include\IncludeRenderer;
use Alto\CommonMark\Tests\FunctionMocks\FunctionMockRegistry;
use Alto\CommonMark\Tests\Utils\HtmlUtils;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(IncludeExtension::class)]
#[CoversClass(IncludeBlock::class)]
#[CoversClass(IncludeBlockContinueParser::class)]
#[CoversClass(IncludeBlockParser::class)]
#[CoversClass(IncludeProcessor::class)]
#[CoversClass(IncludeRenderer::class)]
final class IncludeExtensionTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__.'/../../FunctionMocks/FunctionMockRegistry.php';
        require_once __DIR__.'/../../FunctionMocks/IncludeFunctionMocks.php';
    }

    public function tearDown(): void
    {
        FunctionMockRegistry::reset();
    }

    /**
     * @return array<string, array<string>>
     */
    public static function fixtureDataset(): array
    {
        $base = __DIR__.'/../../Fixtures/Extension/Include';

        return [
            'basic' => ["$base/basic.md", "$base/basic.html"],
            'line-range-context' => ["$base/line-range-context.md", "$base/line-range-context.html"],
            'quoted-line-range-context' => ["$base/quoted-line-range-context.md", "$base/quoted-line-range-context.html"],
            'indented-directive' => ["$base/indented-directive.md", "$base/indented-directive.html"],
            'path-traversal' => ["$base/path-traversal.md", "$base/path-traversal.html"],
            'single-line-range' => ["$base/single-line-range.md", "$base/single-line-range.html"],
            'malformed-options' => ["$base/malformed-options.md", "$base/malformed-options.html"],
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public static function errorDataset(): array
    {
        return [
            'invalid-syntax' => [
                '@include fragments/intro.md',
                '<div class="include-error">Include error: Invalid include syntax</div>',
            ],
            'missing-file' => [
                '@include "missing.md"',
                '<div class="include-error">Include error: File not found: missing.md</div>',
            ],
            'disallowed-extension' => [
                '@include "fragments/intro.txt"',
                '<div class="include-error">Include error: File type not allowed: .txt</div>',
            ],
            'absolute-path-traversal' => [
                '@include "/etc/passwd.md"',
                '<div class="include-error">Include error: File not found: /etc/passwd.md</div>',
            ],
        ];
    }

    #[DataProvider('fixtureDataset')]
    public function testFixtures(string $mdPath, string $expectedPath): void
    {
        $md = file_get_contents($mdPath);
        $expected = HtmlUtils::normalize(file_get_contents($expectedPath) ?: '');

        $conv = $this->createConverter();
        $actual = HtmlUtils::normalize((string) $conv->convert($md));

        self::assertSame($expected, $actual, "Fixture failed for $mdPath");
    }

    #[DataProvider('errorDataset')]
    public function testIncludeErrors(string $markdown, string $expected): void
    {
        $conv = $this->createConverter();
        $actual = HtmlUtils::normalize((string) $conv->convert($markdown));

        self::assertSame($expected, $actual);
    }

    public function testFileTooLargeError(): void
    {
        $conv = $this->createConverter(maxFileSize: 10);
        $actual = HtmlUtils::normalize((string) $conv->convert('@include "fragments/intro.md"'));

        self::assertSame(
            '<div class="include-error">Include error: File too large: 0MB (max: 0MB)</div>',
            $actual
        );
    }

    public function testMaxDepthZeroProducesEmptyInclude(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new IncludeExtension(
            basePath: __DIR__.'/../../Fixtures/Extension/Include',
            maxDepth: 0,
        ));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert('@include "fragments/intro.md"'));

        // With maxDepth=0, the include directive is valid but content should not be parsed/rendered
        self::assertSame('', $actual);
    }

    public function testIndentedDirectiveIsNotParsedAsIncludeBlock(): void
    {
        $conv = $this->createConverter();
        $actual = HtmlUtils::normalize((string) $conv->convert('    @include "fragments/intro.md"'));

        self::assertStringNotContainsString('include-error', $actual);
        self::assertStringNotContainsString('Introduction', $actual);
        self::assertStringContainsString('<code>', $actual);
    }

    public function testSingleLineRangeImportsOneLine(): void
    {
        $conv = $this->createConverter();
        $actual = HtmlUtils::normalize((string) $conv->convert('@include "fragments/intro.md" {lines: 3}'));

        self::assertStringContainsString('This is the introduction section.', $actual);
        self::assertStringNotContainsString('Key Points', $actual);
    }

    public function testInvalidLineRangeFallsBackToFullFile(): void
    {
        $conv = $this->createConverter();
        // 'abc' is not a valid range (not n, not n-m) → parseLineRange returns null → full file included
        $actual = HtmlUtils::normalize((string) $conv->convert('@include "fragments/intro.md" {lines: abc}'));

        self::assertStringContainsString('Introduction', $actual);
    }

    public function testInvalidBasePathShowsError(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new IncludeExtension('/nonexistent/path/that/does/not/exist'));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert('@include "intro.md"'));

        self::assertStringContainsString('include-error', $actual);
        self::assertStringContainsString('Path not allowed', $actual);
    }

    public function testEmptyFileContentProducesNoOutput(): void
    {
        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir.'/empty-include.md';
        file_put_contents($tempFile, '');

        try {
            $env = new Environment();
            $env->addExtension(new CommonMarkCoreExtension());
            $env->addExtension(new IncludeExtension($tempDir));
            $conv = new MarkdownConverter($env);

            $actual = HtmlUtils::normalize((string) $conv->convert('@include "empty-include.md"'));

            self::assertSame('', $actual);
        } finally {
            @unlink($tempFile);
        }
    }

    public function testUnreadableFileShowsError(): void
    {
        $tmpDir = sys_get_temp_dir();
        $tmpFile = $tmpDir.'/test_include_readable_'.uniqid().'.md';
        file_put_contents($tmpFile, '# Test');
        $filename = basename($tmpFile);

        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new IncludeExtension($tmpDir));
        $conv = new MarkdownConverter($env);

        FunctionMockRegistry::$isReadableReturnsFalse = true;

        try {
            $actual = HtmlUtils::normalize((string) $conv->convert('@include "'.$filename.'"'));
            self::assertStringContainsString('File not readable', $actual);
        } finally {
            FunctionMockRegistry::reset();
            @unlink($tmpFile);
        }
    }

    public function testFileGetContentsFailureShowsError(): void
    {
        $tmpDir = sys_get_temp_dir();
        $tmpFile = $tmpDir.'/test_include_fgc_'.uniqid().'.md';
        file_put_contents($tmpFile, '# Test');
        $filename = basename($tmpFile);

        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new IncludeExtension($tmpDir));
        $conv = new MarkdownConverter($env);

        FunctionMockRegistry::$fileGetContentsReturnsFalse = true;

        try {
            $actual = HtmlUtils::normalize((string) $conv->convert('@include "'.$filename.'"'));
            self::assertStringContainsString('Failed to read file', $actual);
        } finally {
            FunctionMockRegistry::reset();
            @unlink($tmpFile);
        }
    }

    public function testParseMarkdownReturnsNullForNonEnvironmentInterface(): void
    {
        $envBuilder = $this->createStub(EnvironmentBuilderInterface::class);
        $processor = new IncludeProcessor(10, $envBuilder);
        $method = new \ReflectionMethod($processor, 'parseMarkdown');

        $result = $method->invoke($processor, 'hello');

        self::assertNull($result);
    }

    public function testParseMarkdownCatchesThrowable(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addEventListener(\League\CommonMark\Event\DocumentParsedEvent::class, static function (): void {
            throw new \RuntimeException('Simulated parse error for coverage');
        });

        $processor = new IncludeProcessor(10, $env);
        $method = new \ReflectionMethod($processor, 'parseMarkdown');

        $result = $method->invoke($processor, 'hello world');

        self::assertNull($result);
    }

    private function createConverter(int $maxFileSize = 1048576): MarkdownConverter
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new IncludeExtension(__DIR__.'/../../Fixtures/Extension/Include', maxFileSize: $maxFileSize));

        return new MarkdownConverter($env);
    }
}
