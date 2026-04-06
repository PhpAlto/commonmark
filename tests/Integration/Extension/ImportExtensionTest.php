<?php

declare(strict_types=1);

/*
 * This file is part of the ALTO Commonmark package.
 *
 * © 2025–present Simon André
 *
 * For full copyright and license information, please see
 * the LICENSE file distributed with this source code.
 */

namespace Alto\CommonMark\Tests\Integration\Extension;

use Alto\CommonMark\Extension\Import\ImportBlock;
use Alto\CommonMark\Extension\Import\ImportBlockParser;
use Alto\CommonMark\Extension\Import\ImportExtension;
use Alto\CommonMark\Extension\Import\ImportParser;
use Alto\CommonMark\Extension\Import\ImportRenderer;
use Alto\CommonMark\Tests\FunctionMocks\FunctionMockRegistry;
use Alto\CommonMark\Tests\Utils\HtmlUtils;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImportExtension::class)]
#[CoversClass(ImportBlock::class)]
#[CoversClass(ImportBlockParser::class)]
#[CoversClass(ImportParser::class)]
#[CoversClass(ImportRenderer::class)]
final class ImportExtensionTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__.'/../../FunctionMocks/FunctionMockRegistry.php';
        require_once __DIR__.'/../../FunctionMocks/ImportFunctionMocks.php';
    }

    public function tearDown(): void
    {
        FunctionMockRegistry::reset();
    }

    /**
     * @return array<string, array<string>>
     */
    public static function dataset(): array
    {
        $base = __DIR__.'/../../Fixtures/Extension/Import';

        return [
            'basic' => ["$base/basic.md", "$base/basic.html"],
            'code-options' => ["$base/code-options.md", "$base/code-options.html"],
            'invalid-syntax' => ["$base/invalid-syntax.md", "$base/invalid-syntax.html"],
            'single-line-range' => ["$base/single-line-range.md", "$base/single-line-range.html"],
            'duplicate-imports' => ["$base/duplicate-imports.md", "$base/duplicate-imports.html"],
            'indented-directive' => ["$base/indented-directive.md", "$base/indented-directive.html"],
            'path-traversal' => ["$base/path-traversal.md", "$base/path-traversal.html"],
        ];
    }

    #[DataProvider('dataset')]
    public function testFixtures(string $mdPath, string $expectedPath): void
    {
        $md = file_get_contents($mdPath);
        $expected = HtmlUtils::normalize(file_get_contents($expectedPath) ?: '');

        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new ImportExtension(__DIR__.'/../../Fixtures/Extension/Import'));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert($md));

        self::assertSame($expected, $actual, "Fixture failed for $mdPath");
    }

    public function testMissingFileShowsError(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new ImportExtension(__DIR__.'/../../Fixtures/Extension/Import'));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert('@import "missing.md"'));

        self::assertStringContainsString('Import error: File not found:', $actual);
        self::assertStringContainsString('missing.md', $actual);
    }

    public function testAbsolutePathTraversalIsBlocked(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new ImportExtension(__DIR__.'/../../Fixtures/Extension/Import'));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert('@import "/etc/passwd"'));

        self::assertStringContainsString('import-error', $actual);
        self::assertStringContainsString('Import error:', $actual);
        self::assertStringNotContainsString('root:', $actual);
    }

    public function testMaxDepthExceededShowsError(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new ImportExtension(__DIR__.'/../../Fixtures/Extension/Import', 0));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert('@import "fragments/basic.md"'));

        self::assertStringContainsString('import-error', $actual);
        self::assertStringContainsString('Max import depth exceeded', $actual);
    }

    public function testInvalidBasePathShowsPathNotAllowedError(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new ImportExtension('/nonexistent/path/that/does/not/exist'));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert('@import "basic.md"'));

        self::assertStringContainsString('import-error', $actual);
        self::assertStringContainsString('Path not allowed', $actual);
    }

    public function testInvalidLineRangeFallsBackToWholeFile(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new ImportExtension(__DIR__.'/../../Fixtures/Extension/Import'));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert('@import "fragments/basic.md" {lines: abc}'));

        // Invalid range is ignored; whole file is imported
        self::assertStringContainsString('Imported content', $actual);
    }

    public function testIndentedImportDirectiveIsIgnored(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new ImportExtension(__DIR__.'/../../Fixtures/Extension/Import'));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert('    @import "fragments/basic.md"'));

        self::assertStringNotContainsString('import-block', $actual);
        self::assertStringContainsString('<code>', $actual);
    }

    public function testMalformedOptionIsSkipped(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new ImportExtension(__DIR__.'/../../Fixtures/Extension/Import'));
        $conv = new MarkdownConverter($env);

        // A malformed option pair (no colon) is silently skipped; valid options still apply
        $actual = HtmlUtils::normalize((string) $conv->convert('@import "fragments/sample.php" {badoption, lines: 4-4, lang: php}'));

        self::assertStringContainsString('language-php', $actual);
        self::assertStringContainsString('function add', $actual);
    }

    public function testInvalidLineRangeFallsBackToFullFile(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new ImportExtension(__DIR__.'/../../Fixtures/Extension/Import'));
        $conv = new MarkdownConverter($env);

        // An invalid line range (non-numeric) is ignored; full file is imported
        $actual = HtmlUtils::normalize((string) $conv->convert('@import "fragments/sample.php" {lines: invalid, lang: php}'));

        self::assertStringContainsString('language-php', $actual);
    }

    public function testMixedContentDocumentCoversNonImportLines(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new ImportExtension(__DIR__.'/../../Fixtures/Extension/Import'));
        $conv = new MarkdownConverter($env);

        $md = "# Heading\n\n@import \"fragments/basic.md\"\n\nSome text after.";
        $actual = HtmlUtils::normalize((string) $conv->convert($md));

        self::assertStringContainsString('<h1>Heading</h1>', $actual);
        self::assertStringContainsString('Imported content', $actual);
        self::assertStringContainsString('<p>Some text after.</p>', $actual);
    }

    public function testUnreadableFileShowsError(): void
    {
        $tmpDir = sys_get_temp_dir();
        $tmpFile = $tmpDir.'/test_import_readable_'.uniqid().'.md';
        file_put_contents($tmpFile, '# Test');
        $filename = basename($tmpFile);

        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new ImportExtension($tmpDir));
        $conv = new MarkdownConverter($env);

        FunctionMockRegistry::$isReadableReturnsFalse = true;

        try {
            $actual = HtmlUtils::normalize((string) $conv->convert('@import "'.$filename.'"'));
            self::assertStringContainsString('File not readable', $actual);
        } finally {
            FunctionMockRegistry::reset();
            @unlink($tmpFile);
        }
    }

    public function testFileGetContentsFailureShowsError(): void
    {
        $tmpDir = sys_get_temp_dir();
        $tmpFile = $tmpDir.'/test_import_fgc_'.uniqid().'.md';
        file_put_contents($tmpFile, '# Test');
        $filename = basename($tmpFile);

        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new ImportExtension($tmpDir));
        $conv = new MarkdownConverter($env);

        FunctionMockRegistry::$fileGetContentsReturnsFalse = true;

        try {
            $actual = HtmlUtils::normalize((string) $conv->convert('@import "'.$filename.'"'));
            self::assertStringContainsString('Failed to read file', $actual);
        } finally {
            FunctionMockRegistry::reset();
            @unlink($tmpFile);
        }
    }
}
