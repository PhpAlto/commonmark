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

namespace Alto\CommonMark\Tests\Unit\Extension\LinkRewriter;

use Alto\CommonMark\Extension\LinkRewriter\LinkRewriterExtension;
use Alto\CommonMark\Extension\LinkRewriter\LinkRewriterProcessor;
use Alto\CommonMark\Extension\LinkRewriter\Rewriter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Node;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(LinkRewriterExtension::class)]
#[CoversClass(LinkRewriterProcessor::class)]
#[CoversClass(Rewriter::class)]
final class LinkRewriterExtensionTest extends TestCase
{
    public static function dataProvider(): \Generator
    {
        yield 'relative link' => [
            'markdown' => '[Guide](/getting-started)',
            'expected' => '<p><a href="https://docs.example.com/getting-started">Guide</a></p>',
        ];

        yield 'absolute http link' => [
            'markdown' => '[External](https://other.com/page)',
            'expected' => '<p><a href="https://other.com/page">External</a></p>',
        ];

        yield 'mailto link' => [
            'markdown' => '[Email](mailto:test@example.com)',
            'expected' => '<p><a href="mailto:test@example.com">Email</a></p>',
        ];

        yield 'relative link without leading slash' => [
            'markdown' => '[Docs](docs/intro)',
            'expected' => '<p><a href="https://docs.example.com/docs/intro">Docs</a></p>',
        ];

        yield 'image with relative url' => [
            'markdown' => '![Alt](/images/logo.png)',
            'expected' => '<p><img src="https://docs.example.com/images/logo.png" alt="Alt" /></p>',
        ];

        yield 'image with absolute url' => [
            'markdown' => '![Alt](https://cdn.example.com/logo.png)',
            'expected' => '<p><img src="https://cdn.example.com/logo.png" alt="Alt" /></p>',
        ];
    }

    #[DataProvider('dataProvider')]
    public function testLinkRewritingWithBaseUri(string $markdown, string $expected): void
    {
        $actual = $this->convert($markdown, [
            'base_uri' => 'https://docs.example.com',
        ]);

        self::assertSame($expected, $actual);
    }

    public function testLinkRewritingWithMapOnlyTransformsMatchingUrls(): void
    {
        $actual = $this->convert(
            '[Foo](/foo) [Other](/other) ![Alt](/foo)',
            [
                'map' => [
                    '/foo' => '/bar',
                ],
            ],
        );
        $expected = '<p><a href="/bar">Foo</a> <a href="/other">Other</a> <img src="/bar" alt="Alt" /></p>';

        self::assertSame($expected, $actual);
    }

    public function testLinkRewritingWithPatternLeavesNonMatchingUrlsUntouched(): void
    {
        $actual = $this->convert(
            '[Article](/articles/123) [Guide](/guides/456)',
            [
                'pattern' => [
                    'pattern' => '#/articles/(\\d+)#',
                    'replacement' => '/blog/$1',
                ],
            ],
        );
        $expected = '<p><a href="/blog/123">Article</a> <a href="/guides/456">Guide</a></p>';

        self::assertSame($expected, $actual);
    }

    public function testLinkRewritingWithCallbackCanUseNodeType(): void
    {
        $actual = $this->convert(
            '[Doc](/guide) ![Logo](/logo.svg)',
            [
                'callback' => static function (string $url, Node $node): string {
                    $prefix = $node instanceof Image ? '/images/' : '/links/';

                    return $prefix.ltrim($url, '/');
                },
            ],
        );
        $expected = '<p><a href="/links/guide">Doc</a> <img src="/images/logo.svg" alt="Logo" /></p>';

        self::assertSame($expected, $actual);
    }

    public function testLinkRewritersAreAppliedInConfiguredOrder(): void
    {
        $actual = $this->convert(
            '[Start](/start)',
            [
                'base_uri' => 'https://docs.example.com',
                'map' => [
                    'https://docs.example.com/start' => 'https://docs.example.com/articles/1',
                ],
                'pattern' => [
                    'pattern' => '#/articles/(\\d+)#',
                    'replacement' => '/blog/$1',
                ],
                'callback' => static fn (string $url): string => $url.'?ref=cli',
            ],
        );
        $expected = '<p><a href="https://docs.example.com/blog/1?ref=cli">Start</a></p>';

        self::assertSame($expected, $actual);
    }

    public function testLinkRewritingWithNoRulesIsNoOp(): void
    {
        $markdown = '[Doc](/guide) ![Logo](/logo.svg)';
        $expected = '<p><a href="/guide">Doc</a> <img src="/logo.svg" alt="Logo" /></p>';
        $actual = $this->convert($markdown);

        self::assertSame($expected, $actual);
    }

    public function testThrowsTypeErrorForNonStringBaseUri(): void
    {
        $this->expectException(\TypeError::class);
        new LinkRewriterExtension(['base_uri' => 123]);
    }

    public function testThrowsTypeErrorForNonArrayMap(): void
    {
        $this->expectException(\TypeError::class);
        new LinkRewriterExtension(['map' => 'not-an-array']);
    }

    public function testThrowsTypeErrorForMapWithNonStringEntry(): void
    {
        $this->expectException(\TypeError::class);
        new LinkRewriterExtension(['map' => ['/from' => 42]]);
    }

    public function testThrowsTypeErrorForNonArrayPattern(): void
    {
        $this->expectException(\TypeError::class);
        new LinkRewriterExtension(['pattern' => 'not-an-array']);
    }

    public function testThrowsTypeErrorForPatternMissingKeys(): void
    {
        $this->expectException(\TypeError::class);
        new LinkRewriterExtension(['pattern' => ['only-pattern' => '#foo#']]);
    }

    public function testThrowsTypeErrorForNonCallableCallback(): void
    {
        $this->expectException(\TypeError::class);
        new LinkRewriterExtension(['callback' => 'not-callable']);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function convert(string $markdown, array $config = []): string
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new LinkRewriterExtension($config));
        $converter = new MarkdownConverter($environment);

        return trim((string) $converter->convert($markdown));
    }
}
