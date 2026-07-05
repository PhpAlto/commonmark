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

use Alto\CommonMark\Extension\Source\SourceBlock;
use Alto\CommonMark\Extension\Source\SourceBlockContinueParser;
use Alto\CommonMark\Extension\Source\SourceBlockParser;
use Alto\CommonMark\Extension\Source\SourceExtension;
use Alto\CommonMark\Extension\Source\SourceRenderer;
use Alto\CommonMark\Tests\FunctionMocks\FunctionMockRegistry;
use Alto\CommonMark\Tests\Utils\HtmlUtils;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SourceExtension::class)]
#[CoversClass(SourceBlock::class)]
#[CoversClass(SourceBlockContinueParser::class)]
#[CoversClass(SourceBlockParser::class)]
#[CoversClass(SourceRenderer::class)]
final class SourceExtensionTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__.'/../../FunctionMocks/FunctionMockRegistry.php';
        require_once __DIR__.'/../../FunctionMocks/SourceFunctionMocks.php';
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
        $base = __DIR__.'/../../Fixtures/Extension/Source';

        return [
            'basic' => ["$base/basic.md", "$base/basic.html"],
            'highlight' => ["$base/highlight.md", "$base/highlight.html"],
            'highlight-range' => ["$base/highlight-range.md", "$base/highlight-range.html"],
            'options-with-title' => ["$base/options-with-title.md", "$base/options-with-title.html"],
            'invalid-syntax' => ["$base/invalid-syntax.md", "$base/invalid-syntax.html"],
            'missing-file' => ["$base/missing-file.md", "$base/missing-file.html"],
            'path-traversal' => ["$base/path-traversal.md", "$base/path-traversal.html"],
            'detect-js' => ["$base/detect-js.md", "$base/detect-js.html"],
            'no-language' => ["$base/no-language.md", "$base/no-language.html"],
            'single-line' => ["$base/single-line.md", "$base/single-line.html"],
            'invalid-line-range' => ["$base/invalid-line-range.md", "$base/invalid-line-range.html"],
        ];
    }

    #[DataProvider('dataset')]
    public function testFixtures(string $mdPath, string $expectedPath): void
    {
        $md = file_get_contents($mdPath);
        $expected = HtmlUtils::normalize(file_get_contents($expectedPath) ?: '');

        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new SourceExtension(__DIR__.'/../../Fixtures/Extension/Source'));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert($md));

        self::assertSame($expected, $actual, "Fixture failed for $mdPath");
    }

    public function testAbsolutePathTraversalIsBlocked(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new SourceExtension(__DIR__.'/../../Fixtures/Extension/Source'));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert('@source "/etc/passwd"'));

        self::assertSame(
            '<div class="source-error">Source error: File not found: /etc/passwd</div>',
            $actual
        );
    }

    public function testAllowedExtensionsBlocksDisallowedExtension(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new SourceExtension(
            __DIR__.'/../../Fixtures/Extension/Source',
            ['php'],
        ));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert('@source "samples/utils.js"'));

        self::assertSame(
            '<div class="source-error">Source error: File type not allowed: .js</div>',
            $actual
        );
    }

    public function testAllowedExtensionsPermitsAllowedExtension(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new SourceExtension(
            __DIR__.'/../../Fixtures/Extension/Source',
            ['php'],
        ));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert('@source "samples/Calculator.php" {lines: 5}'));

        self::assertStringContainsString('namespace App;', $actual);
    }

    public function testFileTooLargeShowsError(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new SourceExtension(
            __DIR__.'/../../Fixtures/Extension/Source',
            [],
            true,
            1,
        ));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert('@source "samples/Calculator.php"'));

        self::assertStringContainsString('source-error', $actual);
        self::assertStringContainsString('File too large:', $actual);
    }

    /**
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function detectLanguageProvider(): array
    {
        return [
            'ts' => ['ts', 'typescript'],
            'jsx' => ['jsx', 'jsx'],
            'tsx' => ['tsx', 'tsx'],
            'py' => ['py', 'python'],
            'rb' => ['rb', 'ruby'],
            'go' => ['go', 'go'],
            'rs' => ['rs', 'rust'],
            'java' => ['java', 'java'],
            'c' => ['c', 'c'],
            'cpp' => ['cpp', 'cpp'],
            'cs' => ['cs', 'csharp'],
            'swift' => ['swift', 'swift'],
            'kt' => ['kt', 'kotlin'],
            'scala' => ['scala', 'scala'],
            'r' => ['r', 'r'],
            'sh' => ['sh', 'bash'],
            'ps1' => ['ps1', 'powershell'],
            'sql' => ['sql', 'sql'],
            'html' => ['html', 'html'],
            'xml' => ['xml', 'xml'],
            'css' => ['css', 'css'],
            'scss' => ['scss', 'scss'],
            'less' => ['less', 'less'],
            'json' => ['json', 'json'],
            'yaml' => ['yaml', 'yaml'],
            'toml' => ['toml', 'toml'],
            'ini' => ['ini', 'ini'],
            'md' => ['md', 'markdown'],
            'rst' => ['rst', 'restructuredtext'],
            'tex' => ['tex', 'latex'],
            'dockerfile' => ['dockerfile', 'dockerfile'],
            'makefile' => ['mk', 'makefile'],
            'cmake' => ['cmake', 'cmake'],
            'nginx' => ['nginx', 'nginx'],
            'apache' => ['apache', 'apache'],
            'vim' => ['vim', 'vim'],
            'lua' => ['lua', 'lua'],
            'pl' => ['pl', 'perl'],
            'asm' => ['asm', 'assembly'],
            'diff' => ['diff', 'diff'],
            'csv' => ['csv', 'csv'],
            'txt' => ['txt', 'text'],
            'unknown' => ['xyz', null],
        ];
    }

    #[DataProvider('detectLanguageProvider')]
    public function testDetectLanguage(string $extension, ?string $expected): void
    {
        $parser = new SourceBlockParser(
            __DIR__.'/../../Fixtures/Extension/Source',
            [],
            10 * 1024 * 1024,
        );

        $method = new \ReflectionMethod($parser, 'detectLanguage');
        $actual = $method->invoke($parser, "file.$extension");

        self::assertSame($expected, $actual);
    }

    public function testInvalidBasePathShowsPathNotAllowedError(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new SourceExtension('/nonexistent/path/that/does/not/exist'));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert('@source "samples/Calculator.php"'));

        self::assertStringContainsString('source-error', $actual);
        self::assertStringContainsString('Path not allowed', $actual);
    }

    public function testIndentedDirectiveIsNotParsedAsSourceBlock(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new SourceExtension(__DIR__.'/../../Fixtures/Extension/Source'));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert('    @source "samples/Calculator.php"'));

        self::assertStringNotContainsString('source-block', $actual);
        self::assertStringContainsString('<code>', $actual);
    }

    public function testUnreadableFileShowsError(): void
    {
        $tmpDir = sys_get_temp_dir();
        $tmpFile = $tmpDir.'/test_source_readable_'.uniqid().'.php';
        file_put_contents($tmpFile, '<?php echo 42;');
        $filename = basename($tmpFile);

        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new SourceExtension($tmpDir));
        $conv = new MarkdownConverter($env);

        FunctionMockRegistry::$isReadableReturnsFalse = true;

        try {
            $actual = HtmlUtils::normalize((string) $conv->convert('@source "'.$filename.'"'));
            self::assertStringContainsString('File not readable', $actual);
        } finally {
            FunctionMockRegistry::reset();
            @unlink($tmpFile);
        }
    }

    public function testFileGetContentsFailureShowsError(): void
    {
        $tmpDir = sys_get_temp_dir();
        $tmpFile = $tmpDir.'/test_source_fgc_'.uniqid().'.php';
        file_put_contents($tmpFile, '<?php echo 42;');
        $filename = basename($tmpFile);

        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new SourceExtension($tmpDir));
        $conv = new MarkdownConverter($env);

        FunctionMockRegistry::$fileGetContentsReturnsFalse = true;

        try {
            $actual = HtmlUtils::normalize((string) $conv->convert('@source "'.$filename.'"'));
            self::assertStringContainsString('Failed to read file', $actual);
        } finally {
            FunctionMockRegistry::reset();
            @unlink($tmpFile);
        }
    }
}
