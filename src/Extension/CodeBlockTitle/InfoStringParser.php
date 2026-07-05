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

final readonly class InfoStringParser
{
    /** @return array{lang: string|null, attrs: array<string,string>} */
    public static function parse(string $info): array
    {
        $parts = preg_split('/\s+/', trim($info)) ?: [];
        $lang = $parts[0] ?? null;

        $attrs = [];
        foreach (array_slice($parts, 1) as $chunk) {
            if (preg_match('/^([a-zA-Z0-9_-]+)="([^"]*)"$/', $chunk, $m)) {
                $attrs[$m[1]] = $m[2];
            }
        }

        return ['lang' => $lang, 'attrs' => $attrs];
    }
}
