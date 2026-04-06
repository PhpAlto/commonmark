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

use Alto\CommonMark\Extension\HeadingLevel\HeadingLevelExtension;
use Alto\CommonMark\Extension\HeadingLevel\HeadingLevelProcessor;
use Alto\CommonMark\Tests\Utils\HtmlUtils;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(HeadingLevelExtension::class)]
#[CoversClass(HeadingLevelProcessor::class)]
final class HeadingLevelExtensionTest extends TestCase
{
    /**
     * @return array<string, array{string, string, array<string, mixed>}>
     */
    public static function dataset(): array
    {
        $base = __DIR__.'/../../Fixtures/Extension/HeadingLevel';

        return [
            'basic' => ["$base/basic.md", "$base/basic.html", ['down' => 1]],
            'complex' => ["$base/complex.md", "$base/complex.html", ['map' => [1 => 2, 2 => 3, 3 => 3]]],
            'no-op' => ["$base/no-op.md", "$base/no-op.html", []],
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('dataset')]
    public function testFixtures(string $mdPath, string $expectedPath, array $config): void
    {
        $md = file_get_contents($mdPath);
        $expected = HtmlUtils::normalize(file_get_contents($expectedPath) ?: '');

        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new HeadingLevelExtension($config));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert($md));
        self::assertSame($expected, $actual, "Fixture failed for $mdPath");
    }
}
