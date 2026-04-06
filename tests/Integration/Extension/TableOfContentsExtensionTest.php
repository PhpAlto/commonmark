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

use Alto\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use Alto\CommonMark\Extension\TableOfContents\TocBlock;
use Alto\CommonMark\Extension\TableOfContents\TocBlockContinueParser;
use Alto\CommonMark\Extension\TableOfContents\TocBlockParser;
use Alto\CommonMark\Extension\TableOfContents\TocProcessor;
use Alto\CommonMark\Extension\TableOfContents\TocRenderer;
use Alto\CommonMark\Tests\Utils\HtmlUtils;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableOfContentsExtension::class)]
#[CoversClass(TocBlock::class)]
#[CoversClass(TocBlockContinueParser::class)]
#[CoversClass(TocBlockParser::class)]
#[CoversClass(TocProcessor::class)]
#[CoversClass(TocRenderer::class)]
final class TableOfContentsExtensionTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Load TableOfContentsExtension.php so inner classes (TocBlockParser, etc.)
        // are available for direct instantiation in unit-style tests.
        class_exists(TableOfContentsExtension::class);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function dataset(): array
    {
        $base = __DIR__.'/../../Fixtures/Extension/TableOfContents';

        return [
            'basic' => ["$base/basic.md", "$base/basic.html"],
            'custom-levels' => ["$base/custom-levels.md", "$base/custom-levels.html"],
            'ordered-options' => ["$base/ordered-options.md", "$base/ordered-options.html"],
            'ordered-boolean' => ["$base/ordered-boolean.md", "$base/ordered-boolean.html"],
            'no-matching-levels' => ["$base/no-matching-levels.md", "$base/no-matching-levels.html"],
        ];
    }

    #[DataProvider('dataset')]
    public function testFixtures(string $mdPath, string $expectedPath): void
    {
        $md = file_get_contents($mdPath);
        $expected = HtmlUtils::normalize(file_get_contents($expectedPath) ?: '');

        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new TableOfContentsExtension());
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert($md));

        self::assertSame($expected, $actual, "Fixture failed for $mdPath");
    }

    public function testTryStartReturnsNoneWhenIndented(): void
    {
        $parser = new TocBlockParser(['marker' => '@toc']);
        $cursor = new Cursor('    @toc');
        $parserState = $this->createStub(MarkdownParserStateInterface::class);

        $result = $parser->tryStart($cursor, $parserState);

        self::assertNull($result);
    }
}
