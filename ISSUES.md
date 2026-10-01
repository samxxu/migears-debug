# migears-debug — Known Issues

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> From the miGears Full-Module Code Review Report (5th round, 2026-09-28).

| | |
|---|---|
| Status | **P2 open** |
| Size | src 387 lines (net) · 34 tests · 1 src file |

Legend — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs

## At a glance

| | |
|---|---|
| Unsettled | P0 0 · P1 0 · P2 0 · P3 3 · other 0 |
| Settled | 3 of 6 |
| Waiting on the owner | `P3-4`, `P3-5` |
| Waiting on the coordinator | _nothing_ |
| Waiting on the reviewer | _nothing_ |
| Deferred, owing nobody | `P3-3` |

| id | level | status | title |
|---|---|---|---|
| [`P3-1`](issues/P3-1.md) | P3 | **verified** | The README promises 'any uncaught exception or PHP error will now … |
| [`P3-2`](issues/P3-2.md) | P3 | **verified** | `renderTrace()` still reads `$frame['function']` with no `?? ''` … |
| [`P3-3`](issues/P3-3.md) | P3 | **deferred** | `register()` installs process-global handlers with no unregister path; … |
| [`P3-4`](issues/P3-4.md) | P3 | **open** | README 'Design Philosophy' says 'under 500 lines of code, comments and … |
| [`P3-5`](issues/P3-5.md) | P3 | **open** | sendErrorHeaders() uses hardcoded HTTP/1.1 protocol in the header() … |
| [`G2`](issues/G2.md) | - | **verified** | Strict flags: `phpunit.xml.dist` currently sets `failOnWarning`, … |

## Unclosed

What is left to do here: every item whose `status` is not `verified` or `closed`,
highest severity first. `waiting on` is the party who acts next, read from that status.

| | |
|---|---|
| Unclosed | **3** of 6 |
| By status | `open` 2 · `deferred` 1 |
| Waiting on | owner 2 · - 1 |

| level | item | status | waiting on | title |
|---|---|---|---|---|
| **P3** | [`P3-3`](issues/P3-3.md) | `deferred` | - | `register()` installs process-global handlers with no unregister path; … |
| **P3** | [`P3-4`](issues/P3-4.md) | `open` | owner | README 'Design Philosophy' says 'under 500 lines of code, comments and … |
| **P3** | [`P3-5`](issues/P3-5.md) | `open` | owner | sendErrorHeaders() uses hardcoded HTTP/1.1 protocol in the header() … |

## Verdict

A well-crafted developer debug page with trace rendering and variable dumping; sendErrorHeaders() hardcodes HTTP/1.1 which produces an incorrect status line on HTTP/2 requests.

## Fixed since the last round

G2 strict flags confirmed complete (all 5 + 3 displayDetails); P3-1 shutdown handler for fatal errors confirmed registered; P3-2/P3-3 remain open per owner design choice.

## Test gaps

No test for debugVar() with deeply nested arrays/objects; no test for error handler returning false to defer to PHP's default handler; no test for isFatalError() with all error type constants.

## Verification protocol

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.


---

# migears-debug — 已知问题

> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> 出自 miGears 全模块代码评审报告（5th round，2026-09-28）。

| | |
|---|---|
| 状态 | **P2 待修** |
| 体量 | src 387 行（净）· 34 个用例 · 1 个源文件 |

级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## 状态一览

| | |
|---|---|
| 未了结 | P0 0 · P1 0 · P2 0 · P3 3 · 其他 0 |
| 已了结 | 3 / 6 |
| 等模块主 | `P3-4`, `P3-5` |
| 等协调人 | _无_ |
| 等评审方 | _无_ |
| 已暂缓，不欠谁 | `P3-3` |

| id | 级别 | 状态 | 标题 |
|---|---|---|---|
| [`P3-1`](issues/P3-1.md) | P3 | **verified** | README 承诺「任何未捕获异常或 PHP 错误都会显示调试页」，但 register() 只装了异常处理器与错误处理器，没有 … |
| [`P3-2`](issues/P3-2.md) | P3 | **verified** | renderTrace() 仍直接读 $frame['function']，无 ?? '' … |
| [`P3-3`](issues/P3-3.md) | P3 | **deferred** | register() 装的是进程级全局处理器且无卸载路径；测试类只是重新注册以「恢复」，跑完后 DebugPage … |
| [`P3-4`](issues/P3-4.md) | P3 | **open** | README「设计理念」称「去除注释和空行后不到 500 行代码」，但文件共 509 行；净代码行数可能确实低于 500，但说法不够精确。 |
| [`P3-5`](issues/P3-5.md) | P3 | **open** | sendErrorHeaders() 在 header() 调用中使用硬编码的 HTTP/1.1 协议；在 HTTP/2 … |
| [`G2`](issues/G2.md) | - | **verified** | 严格开关：`phpunit.xml.dist` 目前已开启 … |

## 未关闭

本模块还剩什么要做：所有 `status` 不是 `verified` 或 `closed` 的条目，按严重度从高到低。
`waiting on` 是下一步该动手的一方，由其状态读出。

| | |
|---|---|
| 未关闭 | **3** / 6 |
| 按状态 | `open` 2 · `deferred` 1 |
| 等在谁 | 模块主 2 · - 1 |

| 级别 | 条目 | 状态 | 等在谁 | 标题 |
|---|---|---|---|---|
| **P3** | [`P3-3`](issues/P3-3.md) | `deferred` | - | register() 装的是进程级全局处理器且无卸载路径；测试类只是重新注册以「恢复」，跑完后 DebugPage … |
| **P3** | [`P3-4`](issues/P3-4.md) | `open` | 模块主 | README「设计理念」称「去除注释和空行后不到 500 行代码」，但文件共 509 行；净代码行数可能确实低于 500，但说法不够精确。 |
| **P3** | [`P3-5`](issues/P3-5.md) | `open` | 模块主 | sendErrorHeaders() 在 header() 调用中使用硬编码的 HTTP/1.1 协议；在 HTTP/2 … |

## 结论

一个精心设计的开发者调试页面，含栈追踪渲染与变量打印；sendErrorHeaders() 硬编码 HTTP/1.1，在 HTTP/2 请求下会发送错误的状态行。

## 本轮已修复确认

G2 strict flags confirmed complete (all 5 + 3 displayDetails); P3-1 shutdown handler for fatal errors confirmed registered; P3-2/P3-3 remain open per owner design choice.

## 测试盲区

无深度嵌套数组/对象的 debugVar() 测试；无错误处理器返回 false 委托给 PHP 默认处理器的测试；无所有错误类型常量的 isFatalError() 测试。

## 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- `phpunit.xml.dist` 中的 warning/notice/deprecation/risky 开关：四个全开
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
