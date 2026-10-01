<?php

declare(strict_types=1);

namespace MiGears\Debug;

use Throwable;

/**
 * Developer-friendly exception and error page.
 *
 * Renders a clean, dark-themed HTML page with:
 *   - Exception class and message
 *   - Highlighted code snippet around the error line
 *   - Stack trace with file locations
 *   - Request / server info
 *
 * Single class, zero dependencies. For development use only —
 * never expose detailed error pages in production.
 *
 * Usage:
 *   DebugPage::register();
 *
 *   // Or manually:
 *   echo (new DebugPage())->render($exception);
 */
class DebugPage
{
    public const VERSION = '2.0.0';

    /**
     * Fatal error types that bypass set_error_handler entirely, so they can
     * only be picked up from error_get_last() during shutdown.
     */
    private const FATAL_ERROR_TYPES = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR;

    private int $snippetLines = 15;

    /** Whether an error page has already been emitted in this process. */
    private bool $rendered = false;

    /**
     * Register as global exception, error and shutdown handler.
     *
     * Optionally pass a configured instance to customize behavior
     * (e.g. `DebugPage::register((new DebugPage())->withSnippetLines(20))`).
     *
     * Covers uncaught exceptions, warnings and notices promoted by the error
     * handler, and fatal errors raised after registration — the last via a
     * shutdown function, since they never reach set_error_handler. Conditions
     * that leave no room to render, such as memory exhaustion, are out of
     * reach and left to the SAPI.
     */
    public static function register(?self $page = null): void
    {
        $instance = $page ?? new self();

        set_exception_handler(function (Throwable $e) use ($instance): void {
            $instance->emit($e);
        });

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        register_shutdown_function(function () use ($instance): void {
            $instance->renderFatalError();
        });
    }

    /** Set the total number of lines to show (error line included), minimum 1. */
    public function withSnippetLines(int $lines): self
    {
        $this->snippetLines = max(1, $lines);
        return $this;
    }

    /** Render the exception as an HTML page. */
    public function render(Throwable $e): string
    {
        $class = get_class($e);
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $shortClass = $this->shortClassName($class);
        $file = $e->getFile();
        $line = $e->getLine();
        $snippet = $this->renderSnippet($file, $line);
        $trace = $this->renderTrace($e);
        $serverInfo = $this->renderServerInfo();
        $version = self::VERSION;

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$shortClass}: {$message}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #0f1115;
            color: #e6e6e6;
            line-height: 1.5;
            padding: 20px;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { margin-bottom: 24px; }
        .error-type {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #ff6b6b;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .error-message {
            font-size: 22px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 8px;
            word-break: break-word;
        }
        .error-file {
            font-size: 13px;
            color: #888;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
        }
        .snippet {
            background: #1a1d24;
            border: 1px solid #2a2d36;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .snippet-header {
            padding: 10px 16px;
            background: #14161c;
            border-bottom: 1px solid #2a2d36;
            font-size: 12px;
            color: #888;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
        }
        .snippet-body { padding: 12px 0; overflow-x: auto; }
        .snippet-line {
            display: flex;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
            font-size: 13px;
            line-height: 1.6;
        }
        .line-num {
            display: inline-block;
            width: 50px;
            text-align: right;
            padding-right: 16px;
            color: #555;
            user-select: none;
            flex-shrink: 0;
        }
        .line-code {
            flex: 1;
            padding-left: 16px;
            color: #ccc;
            white-space: pre;
        }
        .snippet-line.highlight {
            background: rgba(255, 107, 107, 0.12);
        }
        .snippet-line.highlight .line-num {
            color: #ff6b6b;
            font-weight: 600;
        }
        .snippet-line.highlight .line-code {
            color: #fff;
            border-left: 2px solid #ff6b6b;
            margin-left: -2px;
            padding-left: 14px;
        }
        .section { margin-bottom: 24px; }
        .section-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .trace-list {
            background: #1a1d24;
            border: 1px solid #2a2d36;
            border-radius: 8px;
            overflow: hidden;
        }
        .trace-item {
            display: flex;
            padding: 10px 16px;
            border-bottom: 1px solid #2a2d36;
            font-size: 13px;
        }
        .trace-item:last-child { border-bottom: none; }
        .trace-num {
            width: 30px;
            color: #555;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
            flex-shrink: 0;
        }
        .trace-content { flex: 1; }
        .trace-call {
            color: #e0e0e0;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
            margin-bottom: 2px;
        }
        .trace-location {
            font-size: 12px;
            color: #777;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 0;
            background: #1a1d24;
            border: 1px solid #2a2d36;
            border-radius: 8px;
            overflow: hidden;
            font-size: 13px;
        }
        .info-row {
            display: contents;
        }
        .info-key {
            padding: 8px 16px;
            color: #888;
            border-bottom: 1px solid #2a2d36;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
        }
        .info-value {
            padding: 8px 16px;
            color: #ccc;
            border-bottom: 1px solid #2a2d36;
            word-break: break-all;
        }
        .info-row:last-child .info-key,
        .info-row:last-child .info-value { border-bottom: none; }
        .footer {
            text-align: center;
            font-size: 11px;
            color: #555;
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid #2a2d36;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="error-type">{$shortClass}</div>
            <div class="error-message">{$message}</div>
            <div class="error-file">{$this->escape($file)}:{$line}</div>
        </div>

        {$snippet}

        <div class="section">
            <div class="section-title">Stack Trace</div>
            <div class="trace-list">
                {$trace}
            </div>
        </div>

        <div class="section">
            <div class="section-title">Request &amp; Server</div>
            <div class="info-grid">
                {$serverInfo}
            </div>
        </div>

        <div class="footer">
            miGears Debug v{$version} &middot; For development use only
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Minimal last-resort page, used when rendering the debug page itself fails.
     *
     * Deliberately plain: no file access, no trace walking, only string
     * concatenation and escaping, so it cannot fail the same way the full
     * page did. The original error is still shown, just without the extras.
     */
    private function renderFallback(Throwable $original, Throwable $failure): string
    {
        $error = $this->escape($this->shortClassName(get_class($original)) . ': ' . $original->getMessage());
        $reason = $this->escape($this->shortClassName(get_class($failure)) . ': ' . $failure->getMessage());

        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '<title>' . $error . '</title></head><body>'
            . '<h1>miGears Debug v' . self::VERSION . '</h1>'
            . '<p>The debug page could not be rendered.</p>'
            . '<p><strong>Original error:</strong> ' . $error . '</p>'
            . '<p><strong>Rendering failed with:</strong> ' . $reason . '</p>'
            . '<p>See the server error log for details.</p>'
            . '</body></html>';
    }

    /** Emit an error page, falling back to the minimal page if rendering fails. */
    private function emit(Throwable $e): void
    {
        $this->rendered = true;
        $this->sendErrorHeaders();

        try {
            echo $this->render($e);
        } catch (Throwable $failure) {
            // Rendering runs inside a handler: a failure here must never
            // become a bare fatal with no page at all.
            echo $this->renderFallback($e, $failure);
        }
    }

    /**
     * Emit a page for a fatal error that never reached the error handler.
     *
     * Runs at shutdown, so it must tolerate having nothing to report: a clean
     * exit, and an exception already handled by the exception handler, both
     * leave error_get_last() unset or carrying a non-fatal error.
     */
    private function renderFatalError(): void
    {
        if ($this->rendered) {
            return;
        }

        $error = error_get_last();
        if ($error === null || !$this->isFatalError($error)) {
            return;
        }

        $this->emit(new \ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        ));
    }

    /**
     * @param array{type: int, message: string, file: string, line: int} $error
     */
    private function isFatalError(array $error): bool
    {
        return (bool) ($error['type'] & self::FATAL_ERROR_TYPES);
    }

    /**
     * Send a 500 response status, unless running under CLI or already sent.
     *
     * The status is set through http_response_code() rather than a literal
     * "HTTP/1.1 500 …" header: the literal form pins the status line to
     * HTTP/1.1 whatever the connection speaks, while this leaves the protocol
     * to the SAPI.
     */
    private function sendErrorHeaders(): void
    {
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
    }

    /** Render the code snippet around the error line. */
    private function renderSnippet(string $file, int $line): string
    {
        if (!is_readable($file)) {
            return '<div class="snippet"><div class="snippet-body" style="padding: 16px; color: #888;">Source file not available</div></div>';
        }

        $lines = file($file);
        if ($lines === false) {
            return '<div class="snippet"><div class="snippet-body" style="padding: 16px; color: #888;">Could not read source file</div></div>';
        }

        $total = count($lines);
        // snippetLines counts total lines shown (error line included), centered.
        // Clamp start so a line beyond the file end never yields an empty snippet.
        $half = intdiv($this->snippetLines, 2);
        $start = max(0, ($line - 1) - $half);
        if ($total > 0) {
            $start = min($start, $total - 1);
        }
        $end = min($total, $start + $this->snippetLines);

        $html = '';
        for ($i = $start; $i < $end; $i++) {
            $num = $i + 1;
            $code = rtrim($lines[$i], "\r\n");
            $isHighlight = $num === $line;
            $class = $isHighlight ? 'snippet-line highlight' : 'snippet-line';
            $html .= '<div class="' . $class . '">'
                . '<span class="line-num">' . $num . '</span>'
                . '<span class="line-code">' . $this->escape($code) . '</span>'
                . '</div>';
        }

        $shortFile = $this->escape($file);

        return <<<HTML
<div class="snippet">
    <div class="snippet-header">{$shortFile}</div>
    <div class="snippet-body">{$html}</div>
</div>
HTML;
    }

    /** Render the stack trace. */
    private function renderTrace(Throwable $e): string
    {
        $trace = $e->getTrace();
        $html = '';

        // Add the top-level file/line as frame 0
        $topHtml = $this->renderTraceItem(0, $e->getFile(), $e->getLine(), '{main}', '');
        $html .= $topHtml;

        foreach ($trace as $i => $frame) {
            $num = $i + 1;
            $file = $frame['file'] ?? '';
            $line = $frame['line'] ?? 0;
            $class = $frame['class'] ?? '';
            $call = $this->traceCall($frame);

            $html .= $this->renderTraceItem($num, $file, $line, $call, $class);
        }

        return $html;
    }

    /**
     * Build a trace frame's call signature.
     *
     * Every key is read with a fallback, so a frame whose shape differs
     * renders as an empty call instead of raising while rendering: a warning
     * here would be turned into an exception by the registered error handler.
     *
     * @param array<string, mixed> $frame
     */
    private function traceCall(array $frame): string
    {
        $class = $frame['class'] ?? '';
        $type = $frame['type'] ?? '';
        $function = $frame['function'] ?? '';

        return $class . $type . $function . '()';
    }

    /** Render a single trace item. */
    private function renderTraceItem(int $num, string $file, int $line, string $call, string $class): string
    {
        $location = $file !== '' ? "{$this->escape($file)}:{$line}" : '[internal function]';
        $escapedCall = $this->escape($call);

        return <<<HTML
<div class="trace-item">
    <span class="trace-num">#{$num}</span>
    <div class="trace-content">
        <div class="trace-call">{$escapedCall}</div>
        <div class="trace-location">{$location}</div>
    </div>
</div>
HTML;
    }

    /** Render server / request info table. */
    private function renderServerInfo(): string
    {
        $info = [
            'Request Method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'Request URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'HTTP Host' => $_SERVER['HTTP_HOST'] ?? 'N/A',
            'Server Name' => $_SERVER['SERVER_NAME'] ?? 'N/A',
            'PHP Version' => PHP_VERSION,
            'PHP SAPI' => PHP_SAPI,
            'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
            'Remote Address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
        ];

        $html = '';
        foreach ($info as $key => $value) {
            $html .= '<div class="info-row">'
                . '<div class="info-key">' . $this->escape($key) . '</div>'
                . '<div class="info-value">' . $this->stringify($value) . '</div>'
                . '</div>';
        }

        return $html;
    }

    /**
     * Convert a $_SERVER value to an escaped string without emitting warnings.
     *
     * A non-scalar value (some SAPIs and bootstraps populate these keys with
     * arrays) must not be cast directly: the resulting "Array to string
     * conversion" warning would be turned into an exception by the registered
     * error handler while rendering inside it.
     */
    private function stringify(mixed $value): string
    {
        return $this->escape(is_scalar($value) ? (string) $value : gettype($value));
    }

    /** Get the short class name (without namespace). */
    private function shortClassName(string $class): string
    {
        $parts = explode('\\', $class);
        return end($parts);
    }

    /** HTML escape helper. */
    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
