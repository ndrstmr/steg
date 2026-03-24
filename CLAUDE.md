# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# Steg — Claude Code Instructions

## Project

`ndrstmr/steg` — The local inference bridge for PHP.
Lightweight, BC-stable PHP 8.4 client for OpenAI-compatible local inference servers.

- **Repository:** github.com/ndrstmr/steg
- **License:** EUPL-1.2
- **Epic:** 5.1 in LS-KI Portal-KI-Plattform (Post-MVP)
- **Skill:** `/php` — load for all implementation work

## Tech Stack

| | |
|---|---|
| PHP | 8.4+ |
| Symfony | 7.4.* (http-client dev/runtime, contracts in core) |
| PHPUnit | 11 |
| PHPStan | 2, Level 9 |
| PHP-CS-Fixer | 3, @Symfony ruleset |

## Design Principles (non-negotiable)

1. **Zero framework dependencies in core** — only `psr/log` + `symfony/http-client-contracts`
2. **BC-Promise from v1.0.0** — no breaking changes to public interfaces without major version bump
3. **All classes `final`**, all value objects `final readonly`
4. **`declare(strict_types=1)`** in every file
5. **PHPStan Level 9 — zero errors, zero ignores** — fix root causes, never suppress

## Workflow — After Every Todo

1. Check and update `README.md`
2. Check and update `docs/*.md`
3. Run all four quality checks:
   ```bash
   composer validate --strict
   vendor/bin/phpstan analyse --no-progress
   vendor/bin/phpunit
   vendor/bin/php-cs-fixer check
   ```
4. Write `ROADMAP.md` entry with duration and context usage

A todo is not done until all four checks are green and docs are updated.

## Quality Checks

```bash
# All four must pass before any commit
composer validate --strict
vendor/bin/phpstan analyse --no-progress   # Level 9, zero errors
vendor/bin/phpunit                          # All tests green
vendor/bin/php-cs-fixer check              # Zero files to fix

# Run a single test suite
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Integration

# Run a single test class
vendor/bin/phpunit tests/Unit/Model/ChatMessageTest.php

# Auto-fix style
vendor/bin/php-cs-fixer fix
```

## Git Rules

- No `Co-Authored-By: Claude` in commit messages
- Versioning via Git tags (`v0.1.0`, `v1.0.0`) — no `version` field in `composer.json`
- Conventional commits: `feat:`, `fix:`, `chore:`, `docs:`, `test:`

## Repository Structure

```
src/
├── StegClient.php                    # Façade
├── Client/
│   ├── InferenceClientInterface.php  # BC-stable contract
│   ├── OpenAiCompatibleClient.php    # vLLM, Ollama, LiteLLM, LocalAI
│   └── MockClient.php               # Tests + offline dev
├── Model/                            # Value Objects (all final readonly)
│   ├── ChatMessage.php
│   ├── CompletionRequest.php
│   ├── CompletionResponse.php
│   ├── CompletionOptions.php         # Presets: default, precise, creative, leichteSprache
│   ├── ModelInfo.php
│   └── StreamChunk.php
├── Exception/
│   ├── StegException.php             # Abstract base
│   ├── ConnectionException.php
│   ├── InferenceException.php
│   ├── ModelNotFoundException.php
│   └── InvalidResponseException.php
└── Factory/
    └── StegClientFactory.php         # DSN + array config

tests/
├── Unit/       Model/, Client/, Factory/
└── Integration/ OpenAiCompatibleClientTest (MockHttpClient)

.claude/skills/php/  # /php skill — load for all PHP work
docs/               # getting-started, configuration, supported-backends, symfony-integration
```

## README / Docs Language

- All public-facing text in **English only**
- No Dataport or openCode.de references until officially published there
- Footer: `Built by 👾 public sector dev crew`

## Current State (2026-03-24)

- ✅ Todo 5.1.1: Repository scaffold complete (40 tests, PHPStan clean, CI green)
- ✅ Repo live: github.com/ndrstmr/steg
- ✅ `/php` skill with 5 references
- 🔜 Next: Todo 5.1.2 — OpenAiCompatibleClient streaming tests + edge cases
