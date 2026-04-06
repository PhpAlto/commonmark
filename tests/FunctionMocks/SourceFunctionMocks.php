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

namespace Alto\CommonMark\Extension\Source;

use Alto\CommonMark\Tests\FunctionMocks\FunctionMockRegistry;

function is_readable(string $filename): bool
{
    if (FunctionMockRegistry::$isReadableReturnsFalse) {
        return false;
    }

    return \is_readable($filename);
}

function file_get_contents(string $filename): string|false
{
    if (FunctionMockRegistry::$fileGetContentsReturnsFalse) {
        return false;
    }

    return \file_get_contents($filename);
}
