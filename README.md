# migears/debug

![Version](https://img.shields.io/badge/version-2.0.0-blue)

Developer-friendly exception and error page for PHP.

A single class that renders beautiful, dark-themed error pages with code snippets, stack traces, and request info. For development use only — never expose detailed error pages in production.

> **Background**: miGears is the open-source successor of **TinyGears**, a
> self-developed PHP framework. It was renamed and open-sourced recently because
> the name *TinyGears* is already taken in the open-source community.

## Features

- **Dark theme** — easy on the eyes during long debugging sessions
- **Code snippet** — highlighted source code around the error line
- **Stack trace** — clean, readable trace with file locations
- **Request info** — method, URI, server details at a glance
- **XSS-safe** — all output is properly HTML-escaped
- **Zero dependencies** — single class, under 500 lines of code (comments and blank lines excluded)
- **One-line setup** — `DebugPage::register()` and you're done

## Boundaries

**In scope**

- The single `DebugPage` class (PSR-4 root `MiGears\Debug`): rendering a dark-themed HTML error page with the exception class and message, a highlighted code snippet around the error line, a numbered stack trace, and a request/server info table.
- `DebugPage::register()` wiring the global handlers (`set_exception_handler`, `set_error_handler`, `register_shutdown_function`), plus `render(Throwable): string` for manual use and `withSnippetLines(int): self`.
- XSS-safe output (every value escaped via `htmlspecialchars` with `ENT_QUOTES | ENT_SUBSTITUTE`) and inline CSS, so the page works without external assets.

**Not in scope (by design)**

- Error logging or persistence — errors are not stored; use `migears/log` for that.
- AJAX / JSON error responses — only HTML pages are rendered.
- Error grouping or analytics across requests.
- Deciding whether the page may be shown (development vs production), and any framework integration beyond the handler pair — that belongs to the caller or the framework.

## Installation

```bash
composer require migears/debug
```

Requires: PHP 8.1+.

## Quick Start

### Automatic Registration

Register as the global exception and error handler:

```php
use MiGears\Debug\DebugPage;

// In your front controller / bootstrap file
DebugPage::register();
```

To customize the registered page, pass a configured instance:

```php
DebugPage::register((new DebugPage())->withSnippetLines(20));
```

That's it. Uncaught exceptions, PHP warnings and notices, and fatal errors raised after registration will now display a beautiful debug page. Conditions that leave no room to render, such as memory exhaustion, are left to the SAPI.

The handlers it installs are process-global and cannot be fully unwound — PHP offers no way to remove the shutdown callback — so call `register()` once, at bootstrap.

### Manual Rendering

```php
use MiGears\Debug\DebugPage;

try {
    // Your code...
} catch (\Throwable $e) {
    $debug = new DebugPage();
    echo $debug->render($e);
}
```

### Customize Snippet Size

```php
$debug = new DebugPage();
$debug->withSnippetLines(20); // Show 20 lines total (error line included)

echo $debug->render($exception);
```

## What You See

1. **Error header** — exception class name and message
2. **Code snippet** — source file with the error line highlighted (15 lines by default)
3. **Stack trace** — numbered frames with call signatures and file locations
4. **Request info** — HTTP method, URI, PHP version, server details

## Screenshot Description

The page features a dark navy background (`#0f1115`) with:
- Red accent color for error type and highlighted line
- Monospace font for code and file paths
- Clean, uncluttered layout
- Fully responsive

## Integration with miGears Web

```php
use MiGears\Web\MiRest;
use MiGears\Debug\DebugPage;

// Only in development!
if ($_ENV['APP_ENV'] === 'dev') {
    DebugPage::register();
}

$rest = new MiRest(__DIR__ . '/resources', 'App\\Resources');
$rest->run();
```

## Important Security Note

**Never use this in production.** Detailed error pages leak sensitive information:

- Full file paths of your source code
- Database credentials in stack traces
- Server configuration details
- Code logic and structure

For production, use a simple, generic error page that only shows "500 Internal Server Error" and logs the actual error on the server.

```php
// Safe production handler
set_exception_handler(function (\Throwable $e) use ($logger) {
    $logger->error($e->getMessage(), ['exception' => $e]);
    http_response_code(500);
    echo 'Internal Server Error';
});
```

## API Reference

| Method | Description |
|--------|-------------|
| `DebugPage::register()` | Register as global exception/error handler |
| `new DebugPage()` | Create a new instance |
| `render(Throwable $e): string` | Render exception as HTML page |
| `withSnippetLines(int $lines): self` | Set total lines shown (error line included) |

## Design Philosophy

miGears Debug follows the miGears philosophy: **minimal, readable, and useful**.

- **One class** — no handlers, no formatters, no dependencies
- **Inline CSS** — no external assets, works in any environment
- **XSS-safe** — all user-provided data is escaped
- **Small enough to read** — under 500 lines of code, comments and blank lines excluded, so it stays true as the module grows

**What we don't do**:
- No AJAX / JSON error responses
- No error logging (use `migears/log` for that)
- No error grouping / analytics
- No framework integration beyond the set_exception_handler / set_error_handler pair

## License

MIT

---

# migears/debug

![Version](https://img.shields.io/badge/version-2.0.0-blue)

开发者友好的 PHP 异常和错误页面。

一个类，渲染出美观的暗色主题错误页面，包含代码片段、调用栈和请求信息。仅用于开发环境 —— 切勿在生产环境暴露详细错误页面。

## 特性

- **暗色主题** — 长时间调试不刺眼
- **代码片段** — 错误行周围的源码高亮显示
- **调用栈** — 清晰易读的栈帧，带函数签名和文件位置
- **请求信息** — 方法、URI、服务器详情一目了然
- **XSS 安全** — 所有输出都经过正确的 HTML 转义
- **零依赖** — 单个类，代码不到 500 行（不计注释与空行）
- **一行代码搞定** — `DebugPage::register()` 就够了

## 边界

**范围内**

- 单个 `DebugPage` 类（PSR-4 根为 `MiGears\Debug`）：渲染暗色主题的 HTML 错误页面，包含异常类名与消息、错误行周围的代码片段高亮、带编号的调用栈，以及请求/服务器信息表。
- `DebugPage::register()` 装配全局处理器（`set_exception_handler`、`set_error_handler`、`register_shutdown_function`）；另提供 `render(Throwable): string` 手动渲染与 `withSnippetLines(int): self`。
- 输出 XSS 安全（所有值经 `htmlspecialchars` 配合 `ENT_QUOTES | ENT_SUBSTITUTE` 转义），并使用内联 CSS，无需外部资源即可工作。

**范围外（刻意不做）**

- 错误日志记录或持久化 —— 不存储错误；请使用 `migears/log`。
- AJAX / JSON 错误响应 —— 只渲染 HTML 页面。
- 跨请求的错误分组或统计分析。
- 判断该页面何时可以展示（开发环境还是生产环境），以及那对处理器之外的任何框架集成 —— 这属于调用方或框架。

## 安装

```bash
composer require migears/debug
```

要求：PHP 8.1+。

## 快速开始

### 自动注册

注册为全局异常和错误处理器：

```php
use MiGears\Debug\DebugPage;

// 在前端控制器 / 引导文件中
DebugPage::register();
```

若需定制注册的页面，传入配置好的实例：

```php
DebugPage::register((new DebugPage())->withSnippetLines(20));
```

就这么简单。未捕获的异常、PHP 警告与通知，以及注册之后发生的致命错误，都会显示一个漂亮的调试页面；至于内存耗尽这类没有余力渲染的情形，则不在覆盖范围内。

这些处理器是进程级全局的，且无法完全卸载——PHP 没有移除 shutdown 回调的办法——因此 `register()` 只应在引导阶段调用一次。

### 手动渲染

```php
use MiGears\Debug\DebugPage;

try {
    // 你的代码...
} catch (\Throwable $e) {
    $debug = new DebugPage();
    echo $debug->render($e);
}
```

### 自定义代码片段大小

```php
$debug = new DebugPage();
$debug->withSnippetLines(20); // 总共显示 20 行（含错误行）

echo $debug->render($exception);
```

## 页面内容

1. **错误头部** — 异常类名和消息
2. **代码片段** — 高亮显示错误行的源文件（默认 15 行）
3. **调用栈** — 带编号的栈帧，含函数签名和文件位置
4. **请求信息** — HTTP 方法、URI、PHP 版本、服务器详情

## 页面设计描述

页面采用深色海军蓝背景（`#0f1115`）：
- 红色强调色用于错误类型和高亮行
- 等宽字体用于代码和文件路径
- 简洁、不杂乱的布局
- 完全响应式

## 与 miGears Web 集成

```php
use MiGears\Web\MiRest;
use MiGears\Debug\DebugPage;

// 仅在开发环境！
if ($_ENV['APP_ENV'] === 'dev') {
    DebugPage::register();
}

$rest = new MiRest(__DIR__ . '/resources', 'App\\Resources');
$rest->run();
```

## 重要安全提醒

**切勿在生产环境使用。** 详细的错误页面会泄露敏感信息：

- 源代码的完整文件路径
- 调用栈中的数据库凭证
- 服务器配置详情
- 代码逻辑和结构

生产环境请使用简单的通用错误页面，只显示"500 Internal Server Error"，实际错误记录在服务器日志中。

```php
// 安全的生产环境处理器
set_exception_handler(function (\Throwable $e) use ($logger) {
    $logger->error($e->getMessage(), ['exception' => $e]);
    http_response_code(500);
    echo 'Internal Server Error';
});
```

## API 参考

| 方法 | 说明 |
|------|------|
| `DebugPage::register()` | 注册为全局异常/错误处理器 |
| `new DebugPage()` | 创建新实例 |
| `render(Throwable $e): string` | 将异常渲染为 HTML 页面 |
| `withSnippetLines(int $lines): self` | 设置总共显示的行数（含错误行） |

## 设计哲学

miGears Debug 遵循 miGears 设计哲学：**极简、可读、实用**。

- **一个类** — 没有 handler、没有格式化器、没有依赖
- **内联 CSS** — 无外部资源，任何环境都能工作
- **XSS 安全** — 所有用户提供的数据都经过转义
- **小到可以读完** — 代码不到 500 行（不计注释与空行），因此不会随模块增长而失真

**我们不做的事**：
- 没有 AJAX / JSON 错误响应
- 没有错误日志记录（用 `migears/log`）
- 没有错误分组 / 统计分析
- 没有框架集成（除了 set_exception_handler / set_error_handler）

## 许可证

MIT
