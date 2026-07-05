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

use Alto\CommonMark\Extension\Tabs\TabGroup;
use Alto\CommonMark\Extension\Tabs\TabGroupContinueParser;
use Alto\CommonMark\Extension\Tabs\TabGroupParser;
use Alto\CommonMark\Extension\Tabs\TabGroupRenderer;
use Alto\CommonMark\Extension\Tabs\TabItem;
use Alto\CommonMark\Extension\Tabs\TabItemRenderer;
use Alto\CommonMark\Extension\Tabs\TabsExtension;
use Alto\CommonMark\Tests\Utils\HtmlUtils;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TabsExtension::class)]
#[CoversClass(TabGroup::class)]
#[CoversClass(TabGroupContinueParser::class)]
#[CoversClass(TabGroupParser::class)]
#[CoversClass(TabGroupRenderer::class)]
#[CoversClass(TabItem::class)]
#[CoversClass(TabItemRenderer::class)]
final class TabsExtensionTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Load TabsExtension.php so inner classes (TabGroupParser, TabItemRenderer, etc.)
        // are available for direct instantiation in unit-style tests.
        class_exists(TabsExtension::class);
    }

    public static function dataset(): array
    {
        $base = __DIR__.'/../../Fixtures/Extension/Tabs';

        return [
            'basic' => ["$base/basic.md", "$base/basic.html"],
            'complex' => ["$base/complex.md", "$base/complex.html"],
            'empty' => ["$base/empty.md", "$base/empty.html"],
            'indented' => ["$base/indented.md", "$base/indented.html"],
        ];
    }

    #[DataProvider('dataset')]
    public function testFixtures(string $mdPath, string $expectedPath): void
    {
        $md = file_get_contents($mdPath);
        $expected = HtmlUtils::normalize(file_get_contents($expectedPath) ?: '');

        $actual = HtmlUtils::normalize($this->convertAndNormalize($md));
        self::assertSame($expected, $actual, "Fixture failed for $mdPath");
    }

    public function testRendersTabListTabsAndPanels(): void
    {
        $html = $this->renderTabGroup([
            new TabItem('Overview', 'Overview content', true),
            new TabItem('Install', 'Install content', false),
        ]);

        self::assertStringContainsString('<div class="tabs-list" role="tablist">', $html);
        self::assertSame(2, preg_match_all('/<button[^>]+role="tab"/', $html));
        self::assertSame(2, preg_match_all('/<div[^>]+role="tabpanel"/', $html));
        self::assertStringContainsString('id="tabs-test-tab-0"', $html);
        self::assertStringContainsString('aria-controls="tabs-test-panel-0"', $html);
        self::assertStringContainsString('id="tabs-test-panel-0"', $html);
        self::assertStringContainsString('aria-labelledby="tabs-test-tab-0"', $html);
        self::assertStringContainsString('class="tab active"', $html);
        self::assertStringContainsString('class="tab-panel active"', $html);
        self::assertStringContainsString('data-panel-index="1" hidden', $html);
    }

    public function testRendersBasicTabContent(): void
    {
        $html = $this->renderTabGroup([
            new TabItem('Overview', "Line 1\nLine <strong>2</strong> & more", true),
        ]);

        self::assertStringContainsString('<div>Line 1<br />', $html);
        self::assertStringContainsString('Line &lt;strong&gt;2&lt;/strong&gt; &amp; more', $html);
        self::assertStringNotContainsString('Line <strong>2</strong> & more', $html);
    }

    public function testReturnsEmptyMarkupWhenNoTabsExist(): void
    {
        $html = $this->renderTabGroup([]);

        self::assertSame('', $html);
    }

    public function testEscapesTabTitles(): void
    {
        $html = $this->renderTabGroup([
            new TabItem('<script>alert(1)</script>', 'Safe content', true),
        ]);

        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function testTryStartReturnsNoneWhenIndented(): void
    {
        $parser = new TabGroupParser();
        $cursor = new Cursor('    @tabs');
        $parserState = $this->createStub(MarkdownParserStateInterface::class);

        $result = $parser->tryStart($cursor, $parserState);

        self::assertNull($result);
    }

    public function testTryStartReturnsNoneForNonTabsLine(): void
    {
        $parser = new TabGroupParser();
        $cursor = new Cursor('## Not a tabs directive');
        $parserState = $this->createStub(MarkdownParserStateInterface::class);

        $result = $parser->tryStart($cursor, $parserState);

        self::assertNull($result);
    }

    public function testContentBeforeFirstTabIsIgnored(): void
    {
        $md = "@tabs\nContent before any tab\n@tab First\nFirst content\n@endtabs";
        $html = $this->convertAndNormalize($md);

        self::assertStringContainsString('First', $html);
        self::assertStringNotContainsString('Content before any tab', $html);
    }

    public function testTabItemRendererReturnsEmpty(): void
    {
        $renderer = new TabItemRenderer();
        $node = $this->createStub(\League\CommonMark\Node\Node::class);
        $childRenderer = $this->createStub(ChildNodeRendererInterface::class);

        self::assertSame('', $renderer->render($node, $childRenderer));
    }

    /**
     * @param array<int, TabItem> $tabs
     */
    private function renderTabGroup(array $tabs): string
    {
        $group = new TabGroup('tabs-test');
        $group->tabs = $tabs;

        $renderer = new TabGroupRenderer([
            'container_class' => 'tabs-container',
            'tabs_class' => 'tabs-list',
            'tab_class' => 'tab',
            'panel_class' => 'tab-panel',
            'active_class' => 'active',
            'generate_ids' => true,
        ]);

        return $renderer->render($group, $this->createStub(ChildNodeRendererInterface::class));
    }

    private function convertAndNormalize(string $md): string
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new TabsExtension());
        $html = trim((new MarkdownConverter($env))->convert($md)->getContent());

        // Normalize dynamic uniqid()-based tab IDs to a stable placeholder
        return preg_replace('/tabs-[0-9a-f]{10,}/i', 'tabs-X', $html) ?? $html;
    }
}
