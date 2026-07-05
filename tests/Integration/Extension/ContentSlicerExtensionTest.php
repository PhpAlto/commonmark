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

use Alto\CommonMark\Extension\ContentSlicer\ContentSlicerExtension;
use Alto\CommonMark\Extension\ContentSlicer\SectionNode;
use Alto\CommonMark\Tests\Utils\HtmlUtils;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContentSlicerExtension::class)]
#[CoversClass(SectionNode::class)]
final class ContentSlicerExtensionTest extends TestCase
{
    public static function dataset(): array
    {
        $base = __DIR__.'/../../Fixtures/Extension/ContentSlicer';

        return [
            'simple' => ["$base/simple.md", "$base/simple.html"],
            'nested' => ["$base/nested.md", "$base/nested.html"],
            'complex' => ["$base/complex.md", "$base/complex.html"],
            'with-preamble' => ["$base/with-preamble.md", "$base/with-preamble.html"],
            'no-headings' => ["$base/no-headings.md", "$base/no-headings.html"],
        ];
    }

    #[DataProvider('dataset')]
    public function testFixtures(string $mdPath, string $expectedPath): void
    {
        $md = file_get_contents($mdPath);
        $expected = HtmlUtils::normalize(file_get_contents($expectedPath) ?: '');

        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new ContentSlicerExtension());
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert($md));
        self::assertSame($expected, $actual, "Fixture failed for $mdPath");
    }

    public function testWithMinLevelZero(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new ContentSlicerExtension(minSectionLevel: 0));

        $conv = new MarkdownConverter($env);
        $actual = HtmlUtils::normalize((string) $conv->convert("# H1\n## H2"));
        $expected = HtmlUtils::normalize('<section><h1>H1</h1><section><h2>H2</h2></section></section>');

        self::assertSame($expected, $actual);
    }

    public function testWithMinLevelTwo(): void
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new ContentSlicerExtension(minSectionLevel: 2));

        $conv = new MarkdownConverter($env);
        $actual = HtmlUtils::normalize((string) $conv->convert("# H1\n## H2\n### H3"));
        $expected = HtmlUtils::normalize('<h1>H1</h1><h2>H2</h2><section><h3>H3</h3></section>');

        self::assertSame($expected, $actual);
    }
}
