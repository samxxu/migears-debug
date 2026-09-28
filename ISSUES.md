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
| Unsettled | P0 0 · P1 0 · P2 0 · P3 3 · other 1 |
| Settled | 0 of 4 |
| Waiting on the owner | `P3-1`, `P3-2`, `P3-3` |
| Waiting on the reviewer | `G2` |
| Waiting on the coordinator | _nothing_ |
| Deferred, owing nobody | _nothing_ |

| id | level | status | title |
|---|---|---|---|
| [`P3-1`](issues/P3-1.md) | P3 | **open** | The README promises 'any uncaught exception or PHP error will now … |
| [`P3-2`](issues/P3-2.md) | P3 | **open** | `renderTrace()` still reads `$frame['function']` with no `?? ''` … |
| [`P3-3`](issues/P3-3.md) | P3 | **open** | `register()` installs process-global handlers with no unregister path; … |
| [`G2`](issues/G2.md) | - | **fixed** | Strict flags: `phpunit.xml.dist` currently sets `failOnWarning`, … |

## Unclosed

What is left to do here: every item whose `status` is not `verified` or `closed`,
highest severity first. `waiting on` is the party who acts next, read from that status.

| | |
|---|---|
| Unclosed | **4** of 4 |
| By status | `open` 3 · `fixed` 1 |
| Waiting on | owner 3 · reviewer 1 |

| level | item | status | waiting on | title |
|---|---|---|---|---|
| **P3** | [`P3-1`](issues/P3-1.md) | `open` | owner | The README promises 'any uncaught exception or PHP error will now … |
| **P3** | [`P3-2`](issues/P3-2.md) | `open` | owner | `renderTrace()` still reads `$frame['function']` with no `?? ''` … |
| **P3** | [`P3-3`](issues/P3-3.md) | `open` | owner | `register()` installs process-global handlers with no unregister path; … |
| **-** | [`G2`](issues/G2.md) | `fixed` | reviewer | Strict flags: `phpunit.xml.dist` currently sets `failOnWarning`, … |

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
| 未了结 | P0 0 · P1 0 · P2 0 · P3 3 · 其他 1 |
| 已了结 | 0 / 4 |
| 等负责人 | `P3-1`, `P3-2`, `P3-3` |
| 等评审方 | `G2` |
| 等协调人 | _无_ |
| 已暂缓，不欠谁 | _无_ |

| id | 级别 | 状态 | 标题 |
|---|---|---|---|
| [`P3-1`](issues/P3-1.md) | P3 | **open** | README 承诺「任何未捕获异常或 PHP 错误都会显示调试页」，但 register() 只装了异常处理器与错误处理器，没有 … |
| [`P3-2`](issues/P3-2.md) | P3 | **open** | renderTrace() 仍直接读 $frame['function']，无 ?? '' … |
| [`P3-3`](issues/P3-3.md) | P3 | **open** | register() 装的是进程级全局处理器且无卸载路径；测试类只是重新注册以「恢复」，跑完后 DebugPage … |
| [`G2`](issues/G2.md) | - | **fixed** | 严格开关：`phpunit.xml.dist` 目前已开启 … |

## 未关闭

本模块还剩什么要做：所有 `status` 不是 `verified` 或 `closed` 的条目，按严重度从高到低。
`waiting on` 是下一步该动手的一方，由其状态读出。

| | |
|---|---|
| 未关闭 | **4** / 4 |
| 按状态 | `open` 3 · `fixed` 1 |
| 等在谁 | 负责人 3 · 评审方 1 |

| 级别 | 条目 | 状态 | 等在谁 | 标题 |
|---|---|---|---|---|
| **P3** | [`P3-1`](issues/P3-1.md) | `open` | 负责人 | README 承诺「任何未捕获异常或 PHP 错误都会显示调试页」，但 register() 只装了异常处理器与错误处理器，没有 … |
| **P3** | [`P3-2`](issues/P3-2.md) | `open` | 负责人 | renderTrace() 仍直接读 $frame['function']，无 ?? '' … |
| **P3** | [`P3-3`](issues/P3-3.md) | `open` | 负责人 | register() 装的是进程级全局处理器且无卸载路径；测试类只是重新注册以「恢复」，跑完后 DebugPage … |
| **-** | [`G2`](issues/G2.md) | `fixed` | 评审方 | 严格开关：`phpunit.xml.dist` 目前已开启 … |

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
