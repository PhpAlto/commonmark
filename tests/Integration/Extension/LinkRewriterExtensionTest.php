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

use Alto\CommonMark\Extension\LinkRewriter\LinkRewriterExtension;
use Alto\CommonMark\Tests\Utils\HtmlUtils;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(LinkRewriterExtension::class)]
final class LinkRewriterExtensionTest extends TestCase
{
    /**
     * @return array<string, array{string, string, array<string, mixed>}>
     */
    public static function dataset(): array
    {
        $base = __DIR__.'/../../Fixtures/Extension/LinkRewriter';

        return [
            'basic' => [
                "$base/basic.md",
                "$base/basic.html",
                ['base_uri' => 'https://docs.example.com'],
            ],
            'complex' => [
                "$base/complex.md",
                "$base/complex.html",
                [
                    'base_uri' => 'https://docs.example.com',
                    'map' => ['https://docs.example.com/start' => 'https://docs.example.com/home'],
                    'pattern' => ['pattern' => '#/articles/(\d+)#', 'replacement' => '/blog/$1'],
                ],
            ],
            'no-rules' => [
                "$base/no-rules.md",
                "$base/no-rules.html",
                [],
            ],
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
        $env->addExtension(new LinkRewriterExtension($config));
        $conv = new MarkdownConverter($env);

        $actual = HtmlUtils::normalize((string) $conv->convert($md));
        self::assertSame($expected, $actual, "Fixture failed for $mdPath");
    }
}
