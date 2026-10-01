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
        DebugPage::register();

        $installed = set_exception_handler(fn() => null);
        restore_exception_handler(); // drop the noop
        restore_exception_handler(); // drop the handler register() installed
        restore_error_handler();     // drop the error handler register() installed

        self::assertNotNull($installed);
    }

    public function testRegisterAcceptsConfiguredInstance(): void
    {
        DebugPage::register((new DebugPage())->withSnippetLines(3));

        $installed = set_exception_handler(fn() => null);
        restore_exception_handler();
        restore_exception_handler();
        restore_error_handler();

        self::assertNotNull($installed);
    }

    public function testRegisterSetsErrorHandler(): void
    {
        DebugPage::register();

        $installed = set_error_handler(fn() => null);
        restore_error_handler();
        restore_error_handler();
        restore_exception_handler();

        self::assertNotNull($installed);
    }

    public function testRegisterHandlersAreUnwoundAfterRegisterTests(): void
    {
        // Each test above calls register(), which stacks one exception and one
        // error handler. Declaration order is the default execution order, so
        // by now both stacks must be back to whatever PHPUnit installed: a
        // leftover DebugPage handler surfaces as a closure carrying a
        // DebugPage instance, which PHPUnit's own handlers do not.
        $exception = set_exception_handler(fn() => null);
        restore_exception_handler();
        $error = set_error_handler(fn() => null);
        restore_error_handler();

        self::assertNull($this->debugPageFromHandler($exception));
        self::assertNull($this->debugPageFromHandler($error));
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

    public function testTraceCallToleratesFrameWithoutFunctionKey(): void
    {
        $method = new \ReflectionMethod($this->debug, 'traceCall');

        // A frame that does not carry 'function' must render an empty call.
        // Read bare, the undefined-key warning is promoted to an exception by
        // the registered error handler — and failOnWarning makes it a failure
        // here already.
        $call = $method->invoke($this->debug, ['file' => 'x.php', 'line' => 1]);

        self::assertSame('()', $call);
    }

    public function testTraceCallJoinsClassTypeAndFunction(): void
    {
        $method = new \ReflectionMethod($this->debug, 'traceCall');

        $call = $method->invoke(
            $this->debug,
            ['class' => 'App\\Thing', 'type' => '->', 'function' => 'run']
        );

        self::assertSame('App\\Thing->run()', $call);
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

    public function testRenderServerInfoHandlesNonScalarValues(): void
    {
        $old = $_SERVER;
        $_SERVER['HTTP_HOST'] = ['example.com', 'other.example.com'];
        try {
            $html = $this->debug->render(new \RuntimeException('test'));
        } finally {
            $_SERVER = $old;
        }

        // Degrades to the type name instead of triggering an
        // "Array to string conversion" warning (failOnWarning turns it fatal).
        self::assertStringContainsString('>array<', $html);
    }

    public function testRenderSubstitutesInvalidUtf8InMessage(): void
    {
        // Build the bytes at runtime so the code snippet cannot contain the
        // substituted result; ENT_SUBSTITUTE renders \xFF as U+FFFD.
        $e = new \RuntimeException('A' . chr(0xFF) . 'B');
        $html = $this->debug->render($e);

        self::assertStringContainsString("A\u{FFFD}B", $html);
        preg_match('#<div class="error-message">(.*?)</div>#s', $html, $m);
        self::assertNotSame('', $m[1] ?? '');
    }

    public function testFooterUsesVersionConstant(): void
    {
        $html = $this->debug->render(new \RuntimeException('test'));

        self::assertStringContainsString('miGears Debug v' . DebugPage::VERSION, $html);
    }

    public function testRenderFallbackProducesMinimalPage(): void
    {
        $method = new \ReflectionMethod($this->debug, 'renderFallback');

        $html = $method->invoke(
            $this->debug,
            new \RuntimeException('original boom'),
            new \LogicException('render boom')
        );

        self::assertStringContainsString('<!DOCTYPE html>', $html);
        self::assertStringContainsString('miGears Debug v' . DebugPage::VERSION, $html);
        self::assertStringContainsString('original boom', $html);
        self::assertStringContainsString('render boom', $html);
    }

    // --- fatal errors ---

    public function testFatalErrorDetection(): void
    {
        $method = new \ReflectionMethod($this->debug, 'isFatalError');

        self::assertTrue($method->invoke($this->debug, $this->errorOfType(E_ERROR)));
        self::assertTrue($method->invoke($this->debug, $this->errorOfType(E_PARSE)));
        self::assertTrue($method->invoke($this->debug, $this->errorOfType(E_COMPILE_ERROR)));
        self::assertFalse($method->invoke($this->debug, $this->errorOfType(E_WARNING)));
        self::assertFalse($method->invoke($this->debug, $this->errorOfType(E_DEPRECATED)));
        self::assertFalse($method->invoke($this->debug, $this->errorOfType(E_USER_NOTICE)));
    }

    public function testEmitRendersPageAndMarksRendered(): void
    {
        $emit = new \ReflectionMethod($this->debug, 'emit');

        ob_start();
        $emit->invoke($this->debug, new \RuntimeException('emitted boom'));
        $output = (string) ob_get_clean();

        self::assertStringContainsString('emitted boom', $output);
        self::assertStringContainsString('miGears Debug v' . DebugPage::VERSION, $output);

        $rendered = new \ReflectionProperty($this->debug, 'rendered');
        self::assertTrue($rendered->getValue($this->debug));
    }

    public function testShutdownRendersFatalErrorInSubprocess(): void
    {
        if (!function_exists('shell_exec')) {
            self::markTestSkipped('shell_exec() is unavailable');
        }

        // A redeclaration is a genuine E_COMPILE_ERROR: not a Throwable, and
        // invisible to set_error_handler, so only the shutdown path can see it.
        // (A syntax error would not do: PHP 8 turns that into a ParseError.)
        $broken = (string) tempnam(sys_get_temp_dir(), 'migears_broken_');
        file_put_contents($broken, "<?php\nfunction migears_probe_duplicate(): void {}\n");

        $output = $this->runPhp(
            'require ' . var_export($this->autoloadPath(), true) . ";\n"
            . "function migears_probe_duplicate(): void {}\n"
            . "\\MiGears\\Debug\\DebugPage::register();\n"
            . 'require ' . var_export($broken, true) . ";\n"
        );
        unlink($broken);

        // Markup only the debug page emits, so the raw PHP fatal cannot satisfy it.
        self::assertStringContainsString('miGears Debug v' . DebugPage::VERSION, $output);
        self::assertStringContainsString('class="error-message"', $output);
    }

    public function testShutdownStaysSilentOnCleanExit(): void
    {
        if (!function_exists('shell_exec')) {
            self::markTestSkipped('shell_exec() is unavailable');
        }

        $output = $this->runPhp(
            'require ' . var_export($this->autoloadPath(), true) . ";\n"
            . "\\MiGears\\Debug\\DebugPage::register();\n"
            . "echo 'DONE';\n"
        );

        self::assertStringContainsString('DONE', $output);
        self::assertStringNotContainsString('class="error-message"', $output);
    }

    // --- response headers ---

    public function testErrorStatusLineFollowsTheConnectionProtocol(): void
    {
        // The built-in server answers with the protocol PHP chose, and it is
        // the one SAPI every environment has. A literal "HTTP/1.1 500 …"
        // status header pins that protocol to 1.1 for an HTTP/1.0 request
        // too, where http_response_code() leaves it to the SAPI.
        $sock = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($sock === false) {
            self::markTestSkipped("cannot bind a loopback socket: {$errstr}");
        }
        $port = (int) substr((string) strrchr((string) stream_socket_get_name($sock, false), ':'), 1);
        fclose($sock);

        $router = (string) tempnam(sys_get_temp_dir(), 'migears_router_');
        file_put_contents(
            $router,
            "<?php\nrequire " . var_export($this->autoloadPath(), true) . ";\n"
            . "\\MiGears\\Debug\\DebugPage::register();\n"
            . "throw new \\RuntimeException('server probe');\n"
        );

        $server = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, $router],
            [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'a'], 2 => ['file', '/dev/null', 'a']],
            $pipes
        );
        if (!is_resource($server)) {
            unlink($router);
            self::markTestSkipped('cannot start the built-in server');
        }

        try {
            $conn = false;
            $deadline = microtime(true) + 3.0;
            while (microtime(true) < $deadline) {
                $conn = @fsockopen('127.0.0.1', $port);
                if ($conn !== false) {
                    break;
                }
                usleep(20 * 1000);
            }
            self::assertIsResource($conn, 'The built-in server did not start in time');

            fwrite($conn, "GET / HTTP/1.0\r\nHost: 127.0.0.1\r\n\r\n");
            $statusLine = (string) fgets($conn);
            fclose($conn);
        } finally {
            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            proc_terminate($server);
            proc_close($server);
            unlink($router);
        }

        self::assertSame('HTTP/1.0 500 Internal Server Error', trim($statusLine));
    }

    // --- README claims ---

    public function testReadmeStatesTheLineCountClaimWithItsMetricInBothHalves(): void
    {
        $readme = (string) file_get_contents(dirname(__DIR__) . '/README.md');

        $claims = 0;
        foreach (explode("\n", $readme) as $line) {
            if (preg_match('/under 500 lines|500 行/u', $line) !== 1) {
                continue;
            }
            $claims++;
            self::assertMatchesRegularExpression(
                '/comments and blank lines excluded|不计注释与空行/u',
                $line,
                'A line-count claim must carry its metric: ' . $line
            );
        }

        // Two statements per README half.
        self::assertSame(4, $claims, 'Both README halves must state the line-count claim');
    }

    public function testReadmeStatesHandlersAreProcessGlobalAndCannotBeFullyUnwoundInBothHalves(): void
    {
        $readme = (string) file_get_contents(dirname(__DIR__) . '/README.md');

        $statements = 0;
        foreach (explode("\n", $readme) as $line) {
            if (preg_match('/process-global|进程级全局/u', $line) !== 1) {
                continue;
            }
            $statements++;
            self::assertMatchesRegularExpression(
                '/cannot be fully unwound|无法完全卸载/u',
                $line,
                'A process-global handler statement must also say registration cannot be fully unwound: ' . $line
            );
        }

        // One statement per README half.
        self::assertSame(2, $statements, 'Both README halves must state that the handlers are process-global');
    }

    private function createExceptionWithTrace(): \RuntimeException
    {
        return new \RuntimeException('test exception');
    }

    /**
     * Return the DebugPage instance a registered handler belongs to, if any.
     *
     * register() installs closures that `use ($instance)`, so the captured
     * object identifies the handler without depending on the absolute depth of
     * the stack — PHPUnit keeps handlers of its own below ours.
     */
    private function debugPageFromHandler(mixed $handler): ?DebugPage
    {
        if (!$handler instanceof \Closure) {
            return null;
        }

        foreach ((new \ReflectionFunction($handler))->getClosureUsedVariables() as $value) {
            if ($value instanceof DebugPage) {
                return $value;
            }
        }

        return null;
    }

    /** @return array{type: int, message: string, file: string, line: int} */
    private function errorOfType(int $type): array
    {
        return ['type' => $type, 'message' => 'boom', 'file' => __FILE__, 'line' => 1];
    }

    private function autoloadPath(): string
    {
        return dirname(__DIR__) . '/vendor/autoload.php';
    }

    /** Run a PHP snippet in a fresh process and return its combined output. */
    private function runPhp(string $code): string
    {
        $script = (string) tempnam(sys_get_temp_dir(), 'migears_script_');
        file_put_contents($script, "<?php\n" . $code);

        $output = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' 2>&1');
        unlink($script);

        return (string) $output;
    }
}
