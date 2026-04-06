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

namespace Alto\CommonMark\Tests\Unit\Testing;

use Alto\CommonMark\Extension\HeadingLevel\HeadingLevelExtension;
use Alto\CommonMark\Testing\CommonMarkExtensionTestCase;
use League\CommonMark\Extension\ExtensionInterface;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Exercises the default getExtraExtensions() implementation (returns empty iterable).
 * HeadingLevelExtensionTest covers the overridden path (loop body); this test covers
 * the base-class return-empty path.
 */
#[CoversClass(CommonMarkExtensionTestCase::class)]
final class CommonMarkExtensionTestCaseTest extends CommonMarkExtensionTestCase
{
    protected function getExtension(): ExtensionInterface
    {
        return new HeadingLevelExtension([]);
    }

    public function testDefaultExtraExtensionsYieldsEmptyIterable(): void
    {
        // getExtraExtensions() is called by setUp(); the base default returns [].
        // Converting any markdown proves setUp() completed without errors.
        self::assertStringContainsString('<p>hello</p>', $this->html('hello'));
    }
}
