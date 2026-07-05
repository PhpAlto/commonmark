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

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Renderer\Block\FencedCodeRenderer;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final readonly class CodeBlockTitleExtension implements ExtensionInterface
{
    public function __construct(
        private ?NodeRendererInterface $baseRenderer = null,
    ) {
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $base = $this->baseRenderer ?? new FencedCodeRenderer();

        $environment->addRenderer(
            FencedCode::class,
            new CodeBlockTitleRenderer($base),
            // higher priority than default so we run first
            10

            // TODO if/check herer: 'return'
        );
    }
}
