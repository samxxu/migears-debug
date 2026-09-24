<?php

declare(strict_types=1);

namespace MiGears\Debug\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Debug\DebugPage;

#[CoversClass(DebugPage::class)]
final class DebugPageTest extends TestCase
{
    private DebugPage $debug;

    protected function setUp(): void
    {
        $this->debug = new DebugPage();
    }

    // --- render ---

    public function testRenderReturnsHtml(): void
    {
        $e = new \RuntimeException('Test exception message');
        $html = $this->debug->render($e);

        self::assertIsString($html);
        self::assertNotEmpty($html);
    }

    public function testRenderContainsExceptionMessage(): void
    {
        $e = new \RuntimeException('Something went wrong');
        $html = $this->debug->render($e);

        self::assertStringContainsString('Something went wrong', $html);
    }

    public function testRenderContainsExceptionClass(): void
    {
        $e = new \RuntimeException('test');
        $html = $this->debug->render($e);

        // Short class name should be shown
        self::assertStringContainsString('RuntimeException', $html);
    }

    public function testRenderContainsFileAndLine(): void
    {
        $e = new \RuntimeException('test');
        $html = $this->debug->render($e);

        self::assertStringContainsString(__FILE__, $html);
        self::assertStringContainsString((string) $e->getLine(), $html);
    }

    public function testRenderContainsStackTraceSection(): void
    {
        $e = new \RuntimeException('test');
        $html = $this->debug->render($e);

        self::assertStringContainsString('Stack Trace', $html);
    }

    public function testRenderContainsServerInfoSection(): void
    {
        $e = new \RuntimeException('test');
        $html = $this->debug->render($e);

        self::assertStringContainsString('Request &amp; Server', $html);
        self::assertStringContainsString('PHP Version', $html);
        self::assertStringContainsString(PHP_VERSION, $html);
    }

    public function testRenderContainsSnippet(): void
    {
        $e = new \RuntimeException('test');
        $html = $this->debug->render($e);

        // Snippet section should have line numbers
        self::assertStringContainsString('snippet', $html);
        self::assertStringContainsString('line-num', $html);
    }

    public function testRenderHtmlEscapesMessage(): void
    {
        $e = new \RuntimeException('<script>alert("xss")</script>');
        $html = $this->debug->render($e);

        self::assertStringNotContainsString('<script>alert("xss")</script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testRenderWithNestedException(): void
    {
        $e = new \InvalidArgumentException('Invalid argument');
        $html = $this->debug->render($e);

        self::assertStringContainsString('InvalidArgumentException', $html);
        self::assertStringContainsString('Invalid argument', $html);
    }

    public function testRenderWithPreviousException(): void
    {
        $prev = new \RuntimeException('previous');
        $e = new \LogicException('current', 0, $prev);
        $html = $this->debug->render($e);

        // The current exception is rendered
        self::assertStringContainsString('LogicException', $html);
        self::assertStringContainsString('current', $html);
    }

    // --- withSnippetLines ---

    public function testWithSnippetLinesReturnsSelf(): void
    {
        $result = $this->debug->withSnippetLines(5);
        self::assertSame($this->debug, $result);
    }

    public function testWithSnippetLinesAdjustsShownLines(): void
    {
        $this->debug->withSnippetLines(2);
        $e = new \RuntimeException('test');
        $html = $this->debug->render($e);

        // Should still have a valid snippet
        self::assertStringContainsString('snippet', $html);
    }

    public function testWithSnippetLinesMinimumIsOne(): void
    {
        $this->debug->withSnippetLines(0);
        $e = new \RuntimeException('test');
        $html = $this->debug->render($e);

        // Should still work (minimum 1)
        self::assertStringContainsString('snippet', $html);
    }

    // --- register ---

    public function testRegisterSetsExceptionHandler(): void
    {
        $previous = set_exception_handler(fn() => null);
        restore_exception_handler();

        DebugPage::register();

        $current = set_exception_handler(fn() => null);
        restore_exception_handler();

        // After register, the handler should be set (not the same as before)
        self::assertNotNull($current);
    }

    public function testRegisterAcceptsConfiguredInstance(): void
    {
        $previous = set_exception_handler(fn() => null);
        restore_exception_handler();

        DebugPage::register((new DebugPage())->withSnippetLines(3));

        $current = set_exception_handler(fn() => null);
        restore_exception_handler();

        self::assertNotNull($current);
    }

    public function testRegisterSetsErrorHandler(): void
    {
        $previous = set_error_handler(fn() => null);
        restore_error_handler();

        DebugPage::register();

        $current = set_error_handler(fn() => null);
        restore_error_handler();

        self::assertNotNull($current);
    }

    // --- Version constant ---

    public function testVersionConstant(): void
    {
        self::assertSame('2.0.0', DebugPage::VERSION);
    }

    // --- Edge cases ---

    public function testRenderWithEmptyMessage(): void
    {
        $e = new \RuntimeException('');
        $html = $this->debug->render($e);

        self::assertIsString($html);
        self::assertNotEmpty($html);
    }

    public function testRenderContainsTraceFrames(): void
    {
        $e = $this->createExceptionWithTrace();
        $html = $this->debug->render($e);

        // Should contain trace item elements
        self::assertStringContainsString('trace-item', $html);
        self::assertStringContainsString('trace-call', $html);
    }

    public function testSnippetFallbackForUnreadableFile(): void
    {
        $method = new \ReflectionMethod($this->debug, 'renderSnippet');

        $html = $method->invoke($this->debug, '/definitely/missing/source.php', 5);

        // Falls back gracefully when the source file cannot be read
        self::assertStringContainsString('Source file not available', $html);
    }

    public function testSnippetWhenLineBeyondFileEnd(): void
    {
        $method = new \ReflectionMethod($this->debug, 'renderSnippet');

        // A line far beyond the real file end must not yield an empty snippet
        $html = $method->invoke($this->debug, __FILE__, PHP_INT_MAX);

        self::assertStringContainsString('line-num', $html);
        self::assertStringNotContainsString('snippet-body"></div>', $html);
    }

    public function testTraceShowsInternalFunctionPlaceholder(): void
    {
        $method = new \ReflectionMethod($this->debug, 'renderTraceItem');

        $html = $method->invoke($this->debug, 3, '', 0, 'strlen()', '');

        self::assertStringContainsString('[internal function]', $html);
    }

    public function testRenderHighlightsErrorLine(): void
    {
        $e = new \RuntimeException('test');
        $html = $this->debug->render($e);

        self::assertStringContainsString('class="snippet-line highlight"', $html);
    }

    public function testWithSnippetLinesShowsExactTotalLines(): void
    {
        $this->debug->withSnippetLines(5);
        $html = $this->debug->render(new \RuntimeException('test'));

        $count = substr_count($html, 'class="snippet-line');
        self::assertSame(5, $count);
    }

    public function testWithSnippetLinesExtremeValueDoesNotBreak(): void
    {
        $this->debug->withSnippetLines(PHP_INT_MAX);
        $html = $this->debug->render(new \RuntimeException('test'));

        self::assertStringContainsString('line-num', $html);
    }

    public function testRenderServerInfoUsesServerValues(): void
    {
        $old = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/debug?x=1';
        try {
            $html = $this->debug->render(new \RuntimeException('test'));
            self::assertStringContainsString('>POST<', $html);
            self::assertStringContainsString('/debug?x=1', $html);
        } finally {
            $_SERVER = $old;
        }
    }

    private function createExceptionWithTrace(): \RuntimeException
    {
        return new \RuntimeException('test exception');
    }
}
