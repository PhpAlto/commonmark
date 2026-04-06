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

namespace Alto\CommonMark\Tests\Unit\Extension\CodeBlockTitle;

use Alto\CommonMark\Extension\CodeBlockTitle\CodeBlockTitleExtension;
use Alto\CommonMark\Extension\CodeBlockTitle\CodeBlockTitleRenderer;
use Alto\CommonMark\Tests\Utils\HtmlUtils;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CodeBlockTitleRenderer::class)]
final class CodeBlockTitleRendererTest extends TestCase
{
    private function convert(string $md): string
    {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new CodeBlockTitleExtension());
        $conv = new MarkdownConverter($env);

        return HtmlUtils::normalize((string) $conv->convert($md));
    }

    public function testWithoutTitleIsPlainCodeblock(): void
    {
        $html = $this->convert("```php\n<?php echo 1;\n```");
        self::assertStringContainsString('<pre><code class="language-php">', $html);
        self::assertStringNotContainsString('<figure', $html);
    }

    public function testWithTitleWrapsInFigureAndFigcaption(): void
    {
        $html = $this->convert("```php title=\"foo.php\"\n<?php echo 1;\n```");
        self::assertStringContainsString('<figure class="code-block has-title"', $html);
        self::assertStringContainsString('<figcaption class="code-title">foo.php</figcaption>', $html);
    }

    public function testNonFencedCodeNodeDelegatesToBaseRenderer(): void
    {
        $node = $this->createStub(Node::class);
        $childRenderer = $this->createStub(ChildNodeRendererInterface::class);
        $base = $this->createMock(NodeRendererInterface::class);
        $base->expects($this->once())
            ->method('render')
            ->with($node, $childRenderer)
            ->willReturn('delegated');

        $renderer = new CodeBlockTitleRenderer($base);
        $result = $renderer->render($node, $childRenderer);

        self::assertSame('delegated', $result);
    }
}
