# Remaining Suppressions (5 total)

## High Priority — Need Refactoring (2 files)

1. `src/Core/ApiClient.php:14` — `@SuppressWarnings(PHPMD.ExcessiveClassComplexity)`
    - Complexity Score: **61/50** (22% over threshold)
    - Refactoring needed: extract into separate service classes (e.g. `ApiAuthenticator`, `HttpClient`, `ResponseParser`, `BatchOperations`)
    - Estimated effort: **2–3 days**

2. `src/Component/Settings/Sync.php:24-26` — Multiple complexity warnings
    - Cyclomatic Complexity: **13/11** (18% over)
    - NPath Complexity: **810/200** (305% over)
    - Refactoring needed: split `syncToDashboard()` into smaller methods (e.g. `syncPlayerSettings`, `syncVoiceSettings`, `syncProjectSettings`)
    - Estimated effort: **1–2 days**

## Low Priority — Acceptable (3 files)

3. `src/Plugin.php:30` — `@SuppressWarnings(PHPMD.CouplingBetweenObjects)`
    - Rationale: Bootstrap class — high coupling is expected and acceptable

4. `src/Component/Settings/Settings.php:31` — `@SuppressWarnings(PHPMD.CouplingBetweenObjects)`
    - Rationale: Settings coordinator — only ~8% over threshold; not worth refactoring

5. `uninstall.php` — `@SuppressWarnings(PHPMD.ExitExpression)`
    - Rationale: Security feature — `exit` is required for WordPress uninstall scripts

## Kept as Requested
- 42 line-length exclusions — kept for readability
- 8 other valid exclusions — database queries, HTTP timeouts, external CDN scripts

## Verification
- ✅ All GrumPHP code-quality checks passing
- ✅ All 434 PHPUnit tests passing (1,432 assertions)
- ✅ No regressions introduced
- ✅ Codebase has 58% fewer suppressions

Summary: suppressions have been reduced and retained only where technically necessary (security, bootstrap) or where larger refactors are required (ApiClient, Sync). The two high-priority files include clear refactoring strategies.