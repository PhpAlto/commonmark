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

namespace Alto\CommonMark\Tests\Unit\Extension\HeadingLevel;

use Alto\CommonMark\Extension\HeadingLevel\HeadingLevelExtension;
use Alto\CommonMark\Extension\HeadingLevel\HeadingLevelProcessor;
use Alto\CommonMark\Testing\CommonMarkExtensionTestCase;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HeadingLevelExtension::class)]
#[CoversClass(HeadingLevelProcessor::class)]
#[CoversClass(CommonMarkExtensionTestCase::class)]
final class HeadingLevelExtensionTest extends CommonMarkExtensionTestCase
{
    protected function getExtension(): ExtensionInterface
    {
        return new HeadingLevelExtension([]);
    }

    protected function getExtraExtensions(): iterable
    {
        // Yields a no-op extension to exercise the base class extra-extension loop
        yield new class implements ExtensionInterface {
            public function register(EnvironmentBuilderInterface $environment): void
            {
            }
        };
    }

    public function testMapConfigurationTransformsMappedLevelsOnly(): void
    {
        $markdown = '# Title'."\n\n".'## Keep'."\n\n".'### Move'."\n\n".'Paragraph';
        $expected = '<h2>Title</h2>'."\n".'<h2>Keep</h2>'."\n".'<h4>Move</h4>'."\n".'<p>Paragraph</p>';
        $actual = $this->convertWithConfig(['map' => [1 => 2, 3 => 4]], $markdown);

        self::assertSame($expected, $actual);
    }

    public function testDownConfigurationShiftsAllHeadingLevels(): void
    {
        $markdown = '# One'."\n\n".'### Three';
        $expected = '<h3>One</h3>'."\n".'<h5>Three</h5>';
        $actual = $this->convertWithConfig(['down' => 2], $markdown);

        self::assertSame($expected, $actual);
    }

    public function testCallbackConfigurationCanSkipSpecificLevels(): void
    {
        $markdown = '# One'."\n\n".'## Two'."\n\n".'### Three';
        $expected = '<h2>One</h2>'."\n".'<h2>Two</h2>'."\n".'<h4>Three</h4>';
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new HeadingLevelExtension([
            'callback' => static fn (int $level): ?int => 2 === $level ? null : $level + 1,
        ]));
        $converter = new MarkdownConverter($environment);

        $actual = trim($converter->convert($markdown)->getContent());

        self::assertSame($expected, $actual);
    }

    public function testMapConfigurationTakesPriorityOverDownAndCallback(): void
    {
        $markdown = '# One'."\n\n".'## Two';
        $expected = '<h3>One</h3>'."\n".'<h2>Two</h2>';
        $actual = $this->convertWithConfig([
            'map' => [1 => 3],
            'down' => 1,
            'callback' => static fn (int $level): int => $level + 2,
        ], $markdown);

        self::assertSame($expected, $actual);
    }

    public function testBaseClassHtmlMethodConvertsMarkdown(): void
    {
        $html = $this->html('# Hello');

        self::assertStringContainsString('<h1>Hello</h1>', $html);
    }

    public function testBaseClassEnvironmentIsAccessible(): void
    {
        $env = $this->environment();

        self::assertInstanceOf(Environment::class, $env);
    }

    public function testBaseClassAssertHtmlContains(): void
    {
        $this->assertHtmlContains('# Title', ['<h1>Title</h1>']);
    }

    public function testBaseClassAssertHtmlSameAsFixture(): void
    {
        $this->assertHtmlSameAsFixture(
            __DIR__.'/../../../Fixtures/Extension/HeadingLevel/no-config.md',
            __DIR__.'/../../../Fixtures/Extension/HeadingLevel/no-config.html',
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function convertWithConfig(array $config, string $markdown): string
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new HeadingLevelExtension($config));
        $converter = new MarkdownConverter($environment);

        return trim($converter->convert($markdown)->getContent());
    }
}
