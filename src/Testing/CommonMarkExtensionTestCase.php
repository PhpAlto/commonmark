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

namespace Alto\CommonMark\Testing;

use Alto\CommonMark\Tests\Utils\HtmlUtils;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\TestCase;

abstract class CommonMarkExtensionTestCase extends TestCase
{
    private ?Environment $env = null;
    private ?MarkdownConverter $converter = null;

    /**
     * Must return the extension under test.
     */
    abstract protected function getExtension(): ExtensionInterface;

    /**
     * Optionally override to add extra extensions (e.g., external ones).
     *
     * @return iterable<ExtensionInterface>
     */
    protected function getExtraExtensions(): iterable
    {
        return [];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->env = new Environment();
        $this->env->addExtension(new CommonMarkCoreExtension());
        $this->env->addExtension($this->getExtension());

        foreach ($this->getExtraExtensions() as $extra) {
            $this->env->addExtension($extra);
        }

        $this->converter = new MarkdownConverter($this->env);
    }

    protected function tearDown(): void
    {
        $this->converter = null;
        $this->env = null;

        parent::tearDown();
    }

    protected function environment(): Environment
    {
        \assert(null !== $this->env);

        return $this->env;
    }

    protected function converter(): MarkdownConverter
    {
        \assert(null !== $this->converter);

        return $this->converter;
    }

    /**
     * Convert Markdown to normalized HTML (whitespace-insensitive).
     */
    protected function html(string $markdown): string
    {
        $out = (string) $this->converter()->convert($markdown);

        return HtmlUtils::normalize($out);
    }

    /**
     * Assert HTML contains all fragments (simple & fast).
     *
     * @param list<string> $mustContain
     */
    protected function assertHtmlContains(string $markdown, array $mustContain): void
    {
        $html = $this->html($markdown);
        foreach ($mustContain as $frag) {
            self::assertStringContainsString($frag, $html);
        }
    }

    /**
     * Compare rendered HTML to a fixture file.
     */
    protected function assertHtmlSameAsFixture(string $markdownFile, string $expectedHtmlFile): void
    {
        $md = \file_get_contents($markdownFile);
        self::assertIsString($md, 'Missing markdown fixture: '.$markdownFile);

        $expected = \file_get_contents($expectedHtmlFile);
        self::assertIsString($expected, 'Missing html fixture: '.$expectedHtmlFile);

        $actual = $this->html($md);
        $expected = HtmlUtils::normalize($expected);

        self::assertSame($expected, $actual, "Fixture mismatch for $markdownFile");
    }
}
