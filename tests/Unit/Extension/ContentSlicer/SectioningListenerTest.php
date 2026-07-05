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

namespace Alto\CommonMark\Tests\Unit\Extension\ContentSlicer;

use Alto\CommonMark\Extension\ContentSlicer\SectioningListener;
use Alto\CommonMark\Extension\ContentSlicer\SectionNode;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Parser\MarkdownParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SectioningListener::class)]
#[CoversClass(SectionNode::class)]
final class SectioningListenerTest extends TestCase
{
    private function createParser(): MarkdownParser
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());

        return new MarkdownParser($environment);
    }

    public function testGroupsH2AndFollowingContentIntoSection(): void
    {
        $document = new Document();
        $heading = new Heading(2);
        $paragraph = new Paragraph();

        $document->appendChild($heading);
        $document->appendChild($paragraph);

        $event = new DocumentParsedEvent($document, $this->createParser());
        $listener = new SectioningListener();
        $listener($event);

        $modifiedDocument = $event->getDocument();

        self::assertCount(1, $modifiedDocument->children());
        $section = $modifiedDocument->firstChild();
        self::assertInstanceOf(SectionNode::class, $section);
        self::assertCount(2, $section->children());
        self::assertSame($heading, $section->firstChild());
        self::assertSame($paragraph, $section->lastChild());
    }

    public function testH1NotWrappedInSection(): void
    {
        $document = new Document();
        $heading1 = new Heading(1);
        $paragraph = new Paragraph();

        $document->appendChild($heading1);
        $document->appendChild($paragraph);

        $event = new DocumentParsedEvent($document, $this->createParser());
        $listener = new SectioningListener();
        $listener($event);

        $modifiedDocument = $event->getDocument();

        // H1 and its content should be direct children, not wrapped in section
        self::assertCount(2, $modifiedDocument->children());
        self::assertSame($heading1, $modifiedDocument->firstChild());
        self::assertSame($paragraph, $modifiedDocument->lastChild());
    }

    public function testH1WithH2Children(): void
    {
        $document = new Document();
        $heading1 = new Heading(1);
        $contentAfterH1 = new Paragraph();
        $heading2 = new Heading(2);
        $contentAfterH2 = new Paragraph();

        $document->appendChild($heading1);
        $document->appendChild($contentAfterH1);
        $document->appendChild($heading2);
        $document->appendChild($contentAfterH2);

        $event = new DocumentParsedEvent($document, $this->createParser());
        $listener = new SectioningListener();
        $listener($event);

        $modifiedDocument = $event->getDocument();

        // Should have: H1, content, section (with H2 and content)
        self::assertCount(3, $modifiedDocument->children());
        self::assertSame($heading1, $modifiedDocument->firstChild());
        self::assertSame($contentAfterH1, $modifiedDocument->children()[1]);

        $section = $modifiedDocument->children()[2];
        self::assertInstanceOf(SectionNode::class, $section);
        self::assertCount(2, $section->children());
        self::assertSame($heading2, $section->firstChild());
        self::assertSame($contentAfterH2, $section->lastChild());
    }

    public function testCreatesMultipleSectionsForMultipleH2(): void
    {
        $document = new Document();
        $heading1 = new Heading(2);
        $paragraph1 = new Paragraph();
        $heading2 = new Heading(2);
        $paragraph2 = new Paragraph();

        $document->appendChild($heading1);
        $document->appendChild($paragraph1);
        $document->appendChild($heading2);
        $document->appendChild($paragraph2);

        $event = new DocumentParsedEvent($document, $this->createParser());
        $listener = new SectioningListener();
        $listener($event);

        $modifiedDocument = $event->getDocument();

        self::assertCount(2, $modifiedDocument->children());

        $section1 = $modifiedDocument->firstChild();
        self::assertInstanceOf(SectionNode::class, $section1);
        self::assertCount(2, $section1->children());
        self::assertSame($heading1, $section1->firstChild());
        self::assertSame($paragraph1, $section1->lastChild());

        $section2 = $section1->next();
        self::assertInstanceOf(SectionNode::class, $section2);
        self::assertCount(2, $section2->children());
        self::assertSame($heading2, $section2->firstChild());
        self::assertSame($paragraph2, $section2->lastChild());
    }

    public function testPreservesContentBeforeFirstH2(): void
    {
        $document = new Document();
        $paragraphBefore = new Paragraph();
        $heading2 = new Heading(2);
        $paragraphAfter = new Paragraph();

        $document->appendChild($paragraphBefore);
        $document->appendChild($heading2);
        $document->appendChild($paragraphAfter);

        $event = new DocumentParsedEvent($document, $this->createParser());
        $listener = new SectioningListener();
        $listener($event);

        $modifiedDocument = $event->getDocument();

        self::assertCount(2, $modifiedDocument->children());
        self::assertSame($paragraphBefore, $modifiedDocument->firstChild());

        $section = $paragraphBefore->next();
        self::assertInstanceOf(SectionNode::class, $section);
        self::assertCount(2, $section->children());
        self::assertSame($heading2, $section->firstChild());
        self::assertSame($paragraphAfter, $section->lastChild());
    }

    public function testNestedHeadings(): void
    {
        $document = new Document();
        $heading2 = new Heading(2);
        $heading3 = new Heading(3);
        $paragraph = new Paragraph();

        $document->appendChild($heading2);
        $document->appendChild($heading3);
        $document->appendChild($paragraph);

        $event = new DocumentParsedEvent($document, $this->createParser());
        $listener = new SectioningListener();
        $listener($event);

        $modifiedDocument = $event->getDocument();

        // Should have one top-level section (h2)
        self::assertCount(1, $modifiedDocument->children());
        $section = $modifiedDocument->firstChild();
        self::assertInstanceOf(SectionNode::class, $section);

        // Section contains: h2, nested section with h3 and paragraph
        self::assertCount(2, $section->children());
        self::assertSame($heading2, $section->firstChild());

        $subSection = $section->children()[1];
        self::assertInstanceOf(SectionNode::class, $subSection);
        self::assertCount(2, $subSection->children());
        self::assertSame($heading3, $subSection->firstChild());
        self::assertSame($paragraph, $subSection->lastChild());
    }

    public function testHandlesEmptySection(): void
    {
        $document = new Document();
        $heading = new Heading(2);

        $document->appendChild($heading);

        $event = new DocumentParsedEvent($document, $this->createParser());
        $listener = new SectioningListener();
        $listener($event);

        $modifiedDocument = $event->getDocument();

        self::assertCount(1, $modifiedDocument->children());
        $section = $modifiedDocument->firstChild();
        self::assertInstanceOf(SectionNode::class, $section);
        self::assertCount(1, $section->children());
        self::assertSame($heading, $section->firstChild());
    }

    public function testCustomMinSectionLevelZero(): void
    {
        $document = new Document();
        $heading1 = new Heading(1);
        $heading2 = new Heading(2);

        $document->appendChild($heading1);
        $document->appendChild($heading2);

        $event = new DocumentParsedEvent($document, $this->createParser());
        $listener = new SectioningListener(minSectionLevel: 0);
        $listener($event);

        $modifiedDocument = $event->getDocument();

        // With minSectionLevel 0, even h1 should be wrapped
        self::assertCount(1, $modifiedDocument->children());
        $section1 = $modifiedDocument->firstChild();
        self::assertInstanceOf(SectionNode::class, $section1);
        self::assertCount(2, $section1->children());
        self::assertSame($heading1, $section1->firstChild());

        $section2 = $section1->children()[1];
        self::assertInstanceOf(SectionNode::class, $section2);
        self::assertSame($heading2, $section2->firstChild());
    }

    public function testCustomMinSectionLevelTwo(): void
    {
        $document = new Document();
        $heading1 = new Heading(1);
        $heading2 = new Heading(2);
        $heading3 = new Heading(3);

        $document->appendChild($heading1);
        $document->appendChild($heading2);
        $document->appendChild($heading3);

        $event = new DocumentParsedEvent($document, $this->createParser());
        $listener = new SectioningListener(minSectionLevel: 2);
        $listener($event);

        $modifiedDocument = $event->getDocument();

        // With minSectionLevel 2, only h3+ gets wrapped
        self::assertCount(3, $modifiedDocument->children());
        self::assertSame($heading1, $modifiedDocument->children()[0]);
        self::assertSame($heading2, $modifiedDocument->children()[1]);

        $section = $modifiedDocument->children()[2];
        self::assertInstanceOf(SectionNode::class, $section);
        self::assertSame($heading3, $section->firstChild());
    }
}
