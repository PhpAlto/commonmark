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

namespace Alto\CommonMark\Extension\Tabs;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final readonly class TabsExtension implements ExtensionInterface
{
    /**
     * @var array{
     *     container_class: string,
     *     tabs_class: string,
     *     tab_class: string,
     *     panel_class: string,
     *     active_class: string,
     *     generate_ids: bool
     * }
     */
    private array $config;

    /**
     * @param array{
     *     container_class?: string,
     *     tabs_class?: string,
     *     tab_class?: string,
     *     panel_class?: string,
     *     active_class?: string,
     *     generate_ids?: bool
     * } $config
     */
    public function __construct(array $config = [])
    {
        $mergedConfig = array_merge([
            'container_class' => 'tabs-container',
            'tabs_class' => 'tabs-list',
            'tab_class' => 'tab',
            'panel_class' => 'tab-panel',
            'active_class' => 'active',
            'generate_ids' => true,
        ], $config);
        /* @var array{container_class: string, tabs_class: string, tab_class: string, panel_class: string, active_class: string, generate_ids: bool} $mergedConfig */
        $this->config = $mergedConfig;
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addBlockStartParser(new TabGroupParser(), 100)
            ->addRenderer(TabGroup::class, new TabGroupRenderer($this->config))
            ->addRenderer(TabItem::class, new TabItemRenderer());
    }
}

final class TabGroup extends AbstractBlock
{
    /**
     * @param list<TabItem> $tabs
     */
    public function __construct(
        public readonly string $id,
        public array $tabs = [],
    ) {
        parent::__construct();
    }
}

final class TabItem extends AbstractBlock
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        public readonly bool $active = false,
    ) {
        parent::__construct();
    }
}

final readonly class TabGroupParser implements BlockStartParserInterface
{
    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        $indent = $cursor->getIndent();

        if ($indent > 3) {
            return BlockStart::none();
        }

        $line = trim($cursor->getRemainder());

        // Check for @tabs start marker
        if (!str_starts_with($line, '@tabs')) {
            return BlockStart::none();
        }

        $cursor->advanceToEnd();

        // Generate unique ID for this tab group
        $id = 'tabs-'.uniqid();

        $block = new TabGroup($id);

        return BlockStart::of(new TabGroupContinueParser($block));
    }
}

final class TabGroupContinueParser extends AbstractBlockContinueParser
{
    private ?string $currentTabTitle = null;
    private string $currentTabContent = '';
    /** @var list<TabItem> */
    private array $tabs = [];
    private bool $isFirstTab = true;

    public function __construct(
        private TabGroup $block,
    ) {
    }

    public function getBlock(): AbstractBlock
    {
        return $this->block;
    }

    public function closeBlock(): void
    {
        // Add the last tab if exists
        if (null !== $this->currentTabTitle) {
            $this->tabs[] = new TabItem(
                $this->currentTabTitle,
                trim($this->currentTabContent),
                false
            );
        }

        // Set first tab as active
        if (!empty($this->tabs)) {
            $this->tabs[0] = new TabItem(
                $this->tabs[0]->title,
                $this->tabs[0]->content,
                true
            );
        }

        $this->block->tabs = $this->tabs;
    }

    public function tryContinue(
        Cursor $cursor,
        BlockContinueParserInterface $activeBlockParser,
    ): BlockContinue {
        $line = $cursor->getRemainder();
        $trimmedLine = trim($line);

        // Check for end marker
        if ('@endtabs' === $trimmedLine) {
            $cursor->advanceToEnd();

            return BlockContinue::finished();
        }

        // Check for new tab marker: @tab "Title" or @tab Title
        if (preg_match('/^@tab\s+(.+)$/', $trimmedLine, $matches)) {
            // Save previous tab if exists
            if (null !== $this->currentTabTitle) {
                $this->tabs[] = new TabItem(
                    $this->currentTabTitle,
                    trim($this->currentTabContent),
                    $this->isFirstTab
                );
                $this->isFirstTab = false;
            }

            // Start new tab
            $this->currentTabTitle = trim($matches[1], '"\'');
            $this->currentTabContent = '';

            $cursor->advanceToEnd();

            return BlockContinue::at($cursor);
        }

        // Add content to current tab
        if (null !== $this->currentTabTitle) {
            $this->currentTabContent .= $line."\n";
            $cursor->advanceToEnd();

            return BlockContinue::at($cursor);
        }

        // If we're here, it's content before the first @tab marker - skip it
        $cursor->advanceToEnd();

        return BlockContinue::at($cursor);
    }
}

final readonly class TabGroupRenderer implements NodeRendererInterface
{
    /**
     * @var array{
     *     container_class: string,
     *     tabs_class: string,
     *     tab_class: string,
     *     panel_class: string,
     *     active_class: string,
     *     generate_ids: bool
     * }
     */
    private array $config;

    /**
     * @param array{
     *     container_class: string,
     *     tabs_class: string,
     *     tab_class: string,
     *     panel_class: string,
     *     active_class: string,
     *     generate_ids: bool
     * } $config
     */
    public function __construct(
        array $config,
    ) {
        $this->config = $config;
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        assert($node instanceof TabGroup);

        if (empty($node->tabs)) {
            return '';
        }

        $html = sprintf(
            '<div class="%s" data-tabs-id="%s">',
            htmlspecialchars($this->config['container_class']),
            htmlspecialchars($node->id)
        );

        // Render tab buttons
        $html .= sprintf('<div class="%s" role="tablist">', htmlspecialchars($this->config['tabs_class']));

        foreach ($node->tabs as $index => $tab) {
            $tabId = $node->id.'-tab-'.$index;
            $panelId = $node->id.'-panel-'.$index;
            $activeClass = $tab->active ? ' '.$this->config['active_class'] : '';

            $html .= sprintf(
                '<button class="%s%s" id="%s" role="tab" aria-selected="%s" aria-controls="%s" data-tab-index="%d">%s</button>',
                htmlspecialchars($this->config['tab_class']),
                $activeClass,
                htmlspecialchars($tabId),
                $tab->active ? 'true' : 'false',
                htmlspecialchars($panelId),
                $index,
                htmlspecialchars($tab->title)
            );
        }

        $html .= '</div>';

        // Render tab panels
        $html .= '<div class="tab-panels">';

        foreach ($node->tabs as $index => $tab) {
            $tabId = $node->id.'-tab-'.$index;
            $panelId = $node->id.'-panel-'.$index;
            $activeClass = $tab->active ? ' '.$this->config['active_class'] : '';
            $hidden = !$tab->active ? ' hidden' : '';

            $html .= sprintf(
                '<div class="%s%s" id="%s" role="tabpanel" aria-labelledby="%s" data-panel-index="%d"%s>',
                htmlspecialchars($this->config['panel_class']),
                $activeClass,
                htmlspecialchars($panelId),
                htmlspecialchars($tabId),
                $index,
                $hidden
            );

            // Content should be parsed as markdown
            $html .= $this->renderContent($tab->content);

            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        // Add JavaScript for tab switching
        $html .= $this->generateScript($node->id);

        return $html;
    }

    private function renderContent(string $content): string
    {
        // In a real implementation, this would parse the content as markdown
        // For now, we'll just escape it
        return '<div>'.nl2br(htmlspecialchars($content)).'</div>';
    }

    private function generateScript(string $id): string
    {
        return sprintf(
            '<script>
            (function() {
                const container = document.querySelector(\'[data-tabs-id="%s"]\');
                if (!container) return;
                
                const tabs = container.querySelectorAll(\'[role="tab"]\');
                const panels = container.querySelectorAll(\'[role="tabpanel"]\');
                
                tabs.forEach(tab => {
                    tab.addEventListener(\'click\', () => {
                        const index = parseInt(tab.dataset.tabIndex);
                        
                        // Deactivate all tabs and panels
                        tabs.forEach(t => {
                            t.classList.remove(\'%s\');
                            t.setAttribute(\'aria-selected\', \'false\');
                        });
                        panels.forEach(p => {
                            p.classList.remove(\'%s\');
                            p.setAttribute(\'hidden\', \'\');
                        });
                        
                        // Activate selected tab and panel
                        tab.classList.add(\'%s\');
                        tab.setAttribute(\'aria-selected\', \'true\');
                        panels[index].classList.add(\'%s\');
                        panels[index].removeAttribute(\'hidden\');
                    });
                });
            })();
            </script>',
            $id,
            $this->config['active_class'],
            $this->config['active_class'],
            $this->config['active_class'],
            $this->config['active_class']
        );
    }
}

final readonly class TabItemRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        // This renderer is not used directly as TabItems are rendered by TabGroupRenderer
        return '';
    }
}
