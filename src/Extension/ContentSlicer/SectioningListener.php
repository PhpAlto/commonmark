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

namespace Alto\CommonMark\Extension\ContentSlicer;

use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;

final readonly class SectioningListener
{
    public function __construct(
        private int $minSectionLevel = 1,
    ) {
    }

    public function __invoke(DocumentParsedEvent $event): void
    {
        $document = $event->getDocument();
        $stack = [];

        foreach ($document->children() as $node) {
            if ($node instanceof Heading) {
                $level = $node->getLevel();

                // Close sections for same or higher level headings
                while (!empty($stack) && $level <= end($stack)->data->get('level')) {
                    array_pop($stack);
                }

                // Only wrap headings above the minimum level threshold in sections
                if ($level > $this->minSectionLevel) {
                    $section = new SectionNode();
                    $section->data->set('level', $level);

                    if (empty($stack)) {
                        $document->appendChild($section);
                    } else {
                        end($stack)->appendChild($section);
                    }
                    $stack[] = $section;
                }
            }

            // Add node to current section or document root
            if (!empty($stack)) {
                end($stack)->appendChild($node);
            } else {
                $document->appendChild($node);
            }
        }
    }
}
