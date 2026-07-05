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

namespace Alto\CommonMark\Extension\LinkRewriter;

use League\CommonMark\Node\Node;

final readonly class Rewriter
{
    /**
     * @return callable(string): string
     */
    public static function baseUri(string $baseUri): callable
    {
        return static function (string $url) use ($baseUri): string {
            if (1 === preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
                return $url;
            }

            return rtrim($baseUri, '/').'/'.ltrim($url, '/');
        };
    }

    /**
     * @param array<string, string> $map
     *
     * @return callable(string): string
     */
    public static function map(array $map): callable
    {
        return static fn (string $url): string => $map[$url] ?? $url;
    }

    /**
     * @return callable(string): string
     */
    public static function pattern(string $pattern, string $replacement): callable
    {
        return static fn (string $url): string => preg_replace($pattern, $replacement, $url) ?? $url;
    }

    /**
     * @param callable(string, Node=): string $callback
     *
     * @return callable(string, Node): string
     */
    public static function callback(callable $callback): callable
    {
        return static fn (string $url, Node $node): string => $callback($url, $node);
    }
}
