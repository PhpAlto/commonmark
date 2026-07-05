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

namespace Alto\CommonMark\Extension\CodeBlockTitle;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;

final readonly class CodeBlockTitleRenderer implements NodeRendererInterface
{
    public function __construct(
        private NodeRendererInterface $base,
    ) {
    }

    /**
     * @return \Stringable|string|null
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        if (!$node instanceof FencedCode) {
            return $this->base->render($node, $childRenderer);
        }

        $info = InfoStringParser::parse($node->getInfo() ?? '');
        $title = $info['attrs']['title'] ?? $info['attrs']['filename'] ?? null;

        // Let the default renderer build <pre><code ...>…</code></pre>
        $inner = $this->base->render($node, $childRenderer);

        if (null === $title) {
            return $inner;
        }

        $escapedTitle = Xml::escape($title);
        $caption = new HtmlElement('figcaption', ['class' => 'code-title'], $escapedTitle);

        return new HtmlElement(
            'figure',
            ['class' => 'code-block has-title', 'data-title' => $title],
            $caption.$inner
        );
    }
}
