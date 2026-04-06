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

namespace Alto\CommonMark\Extension\HeadingLevel;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;

/**
 * @phpstan-type HeadingMap array<int, int>
 * @phpstan-type HeadingLevelConfig array{
 *     map?: HeadingMap,
 *     down?: int,
 *     callback?: callable(int): ?int
 * }
 */
final readonly class HeadingLevelExtension implements ExtensionInterface
{
    /**
     * @param HeadingLevelConfig $config
     */
    public function __construct(
        private array $config = [],
    ) {
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, new HeadingLevelProcessor($this->config));
    }
}
