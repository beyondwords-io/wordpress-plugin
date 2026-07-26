# CLAUDE.md

**[AGENTS.md](AGENTS.md) is the authoritative set of conventions for this repo. Read it before writing code.**

This file exists only so that agents which auto-load `CLAUDE.md` (Claude Code CLI, Claude Desktop) are pointed at it. Don't duplicate conventions here — add them to [AGENTS.md](AGENTS.md).

The non-negotiables, in short:

1. **WordPress VIP compatibility is paramount.** New and modified code passes the `WordPress-VIP-Go` PHPCS ruleset with no `phpcs:ignore` exceptions.
2. **WordPress coding standards for both PHP and JavaScript** — no exceptions.
3. **Keep documentation minimal — comments explain *why*, never *how*.** Default to no comment. One line per inline comment; docblocks are a one-line summary and don't restate a typed signature. Anything needing a detailed explanation goes in a [doc/](doc/) markdown file, not a comment header. Over-documenting is a defect, not diligence. Full rules: [AGENTS.md § Documentation](AGENTS.md#documentation).

Also in [AGENTS.md](AGENTS.md): file/class structure, fully-qualified class references, autoloading, test commands, and the Cypress `@group` / `@covers` convention (**never run the full Cypress suite** — it takes 20+ minutes).
