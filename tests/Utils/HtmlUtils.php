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

namespace Alto\CommonMark\Tests\Utils;

final class HtmlUtils
{
    public static function normalize(string $html): string
    {
        $html = \preg_replace('/>\s+</', '><', $html) ?? $html;

        return \trim($html);
    }
}
