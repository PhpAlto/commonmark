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

namespace Alto\CommonMark\Extension\LinkRewriter;

use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Node;

final readonly class LinkRewriterProcessor
{
    /** @var \Closure(string, Node): string */
    private \Closure $rewriter;

    /**
     * @param \Closure(string, Node): string $rewriter
     */
    public function __construct(\Closure $rewriter)
    {
        $this->rewriter = $rewriter;
    }

    public function onDocumentParsed(DocumentParsedEvent $event): void
    {
        $walker = $event->getDocument()->walker();

        while ($walk = $walker->next()) {
            if (!$walk->isEntering()) {
                continue;
            }

            $node = $walk->getNode();

            if ($node instanceof Link) {
                $node->setUrl(($this->rewriter)($node->getUrl(), $node));
            } elseif ($node instanceof Image) {
                $node->setUrl(($this->rewriter)($node->getUrl(), $node));
            }
        }
    }
}
