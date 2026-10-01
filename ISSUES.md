# migears-debug — Known Issues

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> From the miGears Full-Module Code Review Report (6th round, 2026-10-01).

| | |
|---|---|
| Status | **Best state** |
| Size | src 393 lines (net) · 39 tests · 1 src file |

Legend — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs

## At a glance

| | |
|---|---|
| Unsettled | P0 0 · P1 0 · P2 0 · P3 2 · other 0 |
| Settled | 5 of 7 |
| Waiting on the owner | _nothing_ |
| Waiting on the coordinator | _nothing_ |
| Waiting on the reviewer | `P3-6` |
| Deferred, owing nobody | `P3-3` |

| id | level | status | title |
|---|---|---|---|
| [`P3-1`](issues/P3-1.md) | P3 | **verified** | The README promises 'any uncaught exception or PHP error will now … |
| [`P3-2`](issues/P3-2.md) | P3 | **verified** | `renderTrace()` still reads `$frame['function']` with no `?? ''` … |
| [`P3-3`](issues/P3-3.md) | P3 | **deferred** | `register()` installs process-global handlers with no unregister path; … |
| [`P3-4`](issues/P3-4.md) | P3 | **verified** | README 'Design Philosophy' says 'under 500 lines of code, comments and … |
| [`P3-5`](issues/P3-5.md) | P3 | **verified** | sendErrorHeaders() uses hardcoded HTTP/1.1 protocol in the header() … |
| [`P3-6`](issues/P3-6.md) | P3 | **fixed** | The deferral of P3-3 was recorded as 'no unregister path, documented … |
| [`G2`](issues/G2.md) | - | **verified** | Strict flags: `phpunit.xml.dist` currently sets `failOnWarning`, … |

## Unclosed

What is left to do here: every item whose `status` is not `verified` or `closed`,
highest severity first. `waiting on` is the party who acts next, read from that status.

| | |
|---|---|
| Unclosed | **2** of 7 |
| By status | `deferred` 1 · `fixed` 1 |
| Waiting on | reviewer 1 · - 1 |

| level | item | status | waiting on | title |
|---|---|---|---|---|
| **P3** | [`P3-3`](issues/P3-3.md) | `deferred` | - | `register()` installs process-global handlers with no unregister path; … |
| **P3** | [`P3-6`](issues/P3-6.md) | `fixed` | reviewer | The deferral of P3-3 was recorded as 'no unregister path, documented … |

## Verdict

Both fixes are real and load-bearing; the one thing left is that the documentation the P3-3 deferral called for was never written.

## Fixed since the last round

P3-4 and P3-5 verified by mutation: the README’s line-count claim now carries its metric in both halves, and the error status line goes through http_response_code() so it follows the connection protocol instead of asserting HTTP/1.1.

## Test gaps

renderFallback() (the page rendered when rendering itself fails) has no test; stringify()’s non-scalar branch has no assertion of its own; the unreadable-file path in renderSnippet() is uncovered.

## Verification protocol

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.


---

# migears-debug — 已知问题

> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> 出自 miGears 全模块代码评审报告（6th round，2026-10-01）。

| | |
|---|---|
| 状态 | **状态最好** |
| 体量 | src 393 行（净）· 39 个用例 · 1 个源文件 |

级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## 状态一览

| | |
|---|---|
| 未了结 | P0 0 · P1 0 · P2 0 · P3 2 · 其他 0 |
| 已了结 | 5 / 7 |
| 等模块主 | _无_ |
| 等协调人 | _无_ |
| 等评审方 | `P3-6` |
| 已暂缓，不欠谁 | `P3-3` |

| id | 级别 | 状态 | 标题 |
|---|---|---|---|
| [`P3-1`](issues/P3-1.md) | P3 | **verified** | README 承诺「任何未捕获异常或 PHP 错误都会显示调试页」，但 register() 只装了异常处理器与错误处理器，没有 … |
| [`P3-2`](issues/P3-2.md) | P3 | **verified** | renderTrace() 仍直接读 $frame['function']，无 ?? '' … |
| [`P3-3`](issues/P3-3.md) | P3 | **deferred** | register() 装的是进程级全局处理器且无卸载路径；测试类只是重新注册以「恢复」，跑完后 DebugPage … |
| [`P3-4`](issues/P3-4.md) | P3 | **verified** | README「设计理念」称「去除注释和空行后不到 500 行代码」，但文件共 509 行；净代码行数可能确实低于 500，但说法不够精确。 |
| [`P3-5`](issues/P3-5.md) | P3 | **verified** | sendErrorHeaders() 在 header() 调用中使用硬编码的 HTTP/1.1 协议；在 HTTP/2 … |
| [`P3-6`](issues/P3-6.md) | P3 | **fixed** | P3-3 的暂缓被记为「不加卸载路径，改为写进文档」，条目并断言 README 已写明这些处理器是进程级、无法完全卸载。但两半 README … |
| [`G2`](issues/G2.md) | - | **verified** | 严格开关：`phpunit.xml.dist` 目前已开启 … |

## 未关闭

本模块还剩什么要做：所有 `status` 不是 `verified` 或 `closed` 的条目，按严重度从高到低。
`waiting on` 是下一步该动手的一方，由其状态读出。

| | |
|---|---|
| 未关闭 | **2** / 7 |
| 按状态 | `deferred` 1 · `fixed` 1 |
| 等在谁 | 评审方 1 · - 1 |

| 级别 | 条目 | 状态 | 等在谁 | 标题 |
|---|---|---|---|---|
| **P3** | [`P3-3`](issues/P3-3.md) | `deferred` | - | register() 装的是进程级全局处理器且无卸载路径；测试类只是重新注册以「恢复」，跑完后 DebugPage … |
| **P3** | [`P3-6`](issues/P3-6.md) | `fixed` | 评审方 | P3-3 的暂缓被记为「不加卸载路径，改为写进文档」，条目并断言 README 已写明这些处理器是进程级、无法完全卸载。但两半 README … |

## 结论

两处修复都真实且承重；唯一遗留是 P3-3 暂缓裁定所要求的那段文档始终没有写。

## 本轮已修复确认

P3-4 and P3-5 verified by mutation: the README’s line-count claim now carries its metric in both halves, and the error status line goes through http_response_code() so it follows the connection protocol instead of asserting HTTP/1.1.

## 测试盲区

renderFallback()（渲染自身失败时的降级页）无用例；stringify() 的非标量分支无独立断言；renderSnippet() 的不可读文件路径未覆盖。

## 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- `phpunit.xml.dist` 中的 warning/notice/deprecation/risky 开关：四个全开
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
