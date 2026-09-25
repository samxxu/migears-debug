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

    private int $snippetLines = 15;

    /**
     * Register as global exception and error handler.
     *
     * Optionally pass a configured instance to customize behavior
     * (e.g. `DebugPage::register((new DebugPage())->withSnippetLines(20))`).
     */
    public static function register(?self $page = null): void
    {
        $instance = $page ?? new self();

        set_exception_handler(function (Throwable $e) use ($instance): void {
            if (PHP_SAPI !== 'cli' && !headers_sent()) {
                header('HTTP/1.1 500 Internal Server Error');
                header('Content-Type: text/html; charset=utf-8');
            }
            echo $instance->render($e);
        });

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
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
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $shortClass = $this->shortClassName($class);
        $file = $e->getFile();
        $line = $e->getLine();
        $snippet = $this->renderSnippet($file, $line);
        $trace = $this->renderTrace($e);
        $serverInfo = $this->renderServerInfo();

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
            miGears Debug v2.0.0 &middot; For development use only
        </div>
    </div>
</body>
</html>
HTML;
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
            $type = $frame['type'] ?? '';
            $function = $frame['function'] ?? '';
            $call = $class . $type . $function . '()';

            $html .= $this->renderTraceItem($num, $file, $line, $call, $class);
        }

        return $html;
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
                . '<div class="info-value">' . $this->escape((string) $value) . '</div>'
                . '</div>';
        }

        return $html;
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
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
