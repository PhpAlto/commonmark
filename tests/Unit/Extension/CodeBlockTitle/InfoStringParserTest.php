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

namespace Alto\CommonMark\Tests\Unit\Extension\CodeBlockTitle;

use Alto\CommonMark\Extension\CodeBlockTitle\InfoStringParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InfoStringParser::class)]
final class InfoStringParserTest extends TestCase
{
    public function testParseWithTitle(): void
    {
        $res = InfoStringParser::parse('python title="test.py"');
        self::assertSame('python', $res['lang']);
        self::assertSame('test.py', $res['attrs']['title'] ?? null);
    }

    public function testParseWithoutAttrs(): void
    {
        $res = InfoStringParser::parse('php');
        self::assertSame('php', $res['lang']);
        self::assertArrayNotHasKey('title', $res['attrs']);
    }
}
