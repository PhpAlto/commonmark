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

use Alto\CommonMark\Extension\CodeBlockTitle\CodeBlockTitleExtension;
use Alto\CommonMark\Tests\Utils\HtmlUtils;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CodeBlockTitleExtension::class)]
final class CodeBlockTitleExtensionTest extends TestCase
{
    public static function dataset(): array
    {
        $base = __DIR__.'/../../Fixtures/Extension/CodeBlockTitle';

        return [
            'simple' => ["$base/simple.md", "$base/simple.html"],
            'filename' => ["$base/filename.md", "$base/filename.html"],
            'filename-attr' => ["$base/filename-attr.md", "$base/filename-attr.html"],
            'title-precedence' => ["$base/title-precedence.md", "$base/title-precedence.html"],
            'escaped-title' => ["$base/escaped-title.md", "$base/escaped-title.html"],
        ];
    }

    #[DataProvider('dataset')]
    public function testFixtures(string $mdPath, string $expectedPath): void
    {
        $md = file_get_contents($mdPath);
        $expected = HtmlUtils::normalize(file_get_contents($expectedPath) ?: '');

        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new CodeBlockTitleExtension());
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert($md));
        self::assertSame($expected, $actual, "Fixture failed for $mdPath");
    }
}
