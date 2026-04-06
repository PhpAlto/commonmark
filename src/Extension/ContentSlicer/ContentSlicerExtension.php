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

namespace Alto\CommonMark\Extension\ContentSlicer;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;

final readonly class ContentSlicerExtension implements ExtensionInterface
{
    /**
     * Heading level threshold: headings at this level and below get wrapped in sections.
     * Default: 1 (h1 not wrapped, h2+ wrapped)
     * Set to 0 to wrap all headings, 2 to wrap only h3+, etc.
     */
    public function __construct(
        private int $minSectionLevel = 1,
    ) {
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, new SectioningListener($this->minSectionLevel));
        $environment->addRenderer(SectionNode::class, new SectionNode());
    }
}
