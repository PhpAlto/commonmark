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

namespace Alto\CommonMark\Tests\FunctionMocks;

/**
 * Static registry used by namespace-level function overrides in tests.
 * When a flag is true the override returns a controlled value instead of
 * calling the real built-in function.
 */
final class FunctionMockRegistry
{
    public static bool $isReadableReturnsFalse = false;

    public static bool $fileGetContentsReturnsFalse = false;

    public static function reset(): void
    {
        self::$isReadableReturnsFalse = false;
        self::$fileGetContentsReturnsFalse = false;
    }
}
