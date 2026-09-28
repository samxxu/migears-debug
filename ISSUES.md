# migears-debug — Known Issues / 已知问题

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> From the miGears Full-Module Code Review Report (4th round, 2026-09-27).

| | |
|---|---|
| Status / 状态 | **P0 cleared / P0 已清零** |
| Size / 体量 | src 441 lines (352 net) · 30 tests · 1 src file |

Legend / 图例 — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs
级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## At a glance / 状态一览

| | |
|---|---|
| Items / 条目 | P0 0 · P1 0 · P2 0 · P3 3 · other 1 |
| Answered / 已回复 | 1 of 4 |
| Waiting / 等待回复 | `P3-1`, `P3-2`, `P3-3` |

| id | level | status | title |
|---|---|---|---|
| [`P3-1`](issues/P3-1.md) | P3 | **open** | The README promises 'any uncaught exception or PHP error will now … |
| [`P3-2`](issues/P3-2.md) | P3 | **open** | `renderTrace()` still reads `$frame['function']` with no `?? ''` … |
| [`P3-3`](issues/P3-3.md) | P3 | **open** | `register()` installs process-global handlers with no unregister path; … |
| [`G2`](issues/G2.md) | - | **fixed** | Strict flags: `phpunit.xml.dist` currently sets `failOnWarning`, … |

## Verdict / 结论

The rendering path is now defensive in the right places. What remains is a documentation over-promise about PHP errors and one continuing missing fallback in the trace renderer.

渲染路径的防御已做对。剩下的是 README 对「PHP 错误」的过度承诺，以及堆栈渲染里那处一直没补的兜底。

## Fixed since the last round / 本轮已修复确认

上一轮 4 项中 3 项修复：renderServerInfo 对非标量改用 stringify 且渲染外层加了兜底、消息与 escape 补上 ENT_SUBSTITUTE、页脚改用 VERSION 常量（均有测试钉住）。 

## Test gaps / 测试盲区

No test for a trace frame missing `function`; no test for a genuine fatal error (E_ERROR), which is exactly the gap the README promise hides; the global handler registered by `register()` is never unregistered, so it stays installed after the test class finishes.

无「trace 帧缺 function 键」用例；无真正致命错误（E_ERROR）用例——正是 README 承诺遮住的那块；register() 装的全局处理器从不卸载，测试类跑完后仍挂在进程上。

## Verification protocol / 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
