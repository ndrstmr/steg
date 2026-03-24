# Changelog

All notable changes to `ndrstmr/steg` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-03-24

First stable release. BC-promise starts here — no breaking changes to public interfaces
without a major version bump.

### Added

- `InferenceClientInterface` — BC-stable contract for all LLM inference communication
- `OpenAiCompatibleClient` — Full HTTP client for vLLM, Ollama, LiteLLM, LocalAI, llama.cpp
  - Streaming via SSE (`stream()` returns `Generator<StreamChunk>`)
  - Model listing (`listModels()`)
  - Health check (`isHealthy()`)
  - Full exception mapping (4xx/5xx → typed exceptions)
- `MockClient` — deterministic test double
  - Fixed response cycling via `withResponses()`
  - Dynamic responses via `withCallback()`
  - Streaming support
- `StegClient` — convenience façade with `ask()`, `chat()`, `complete()`, `stream()`
- `StegClientFactory` — DSN-based and array-config-based factory
  - Supported DSN schemes: `vllm://`, `ollama://`, `litellm://`, `localai://`, `llama://`, `mock://`
- Value Objects (`final readonly`):
  - `ChatMessage` with named constructors (`user()`, `system()`, `assistant()`)
  - `CompletionRequest`, `CompletionResponse`, `StreamChunk`, `ModelInfo`
  - `CompletionOptions` with presets: `default()`, `precise()`, `creative()`, `leichteSprache()`
- Exception hierarchy:
  - `StegException` (abstract base)
  - `ConnectionException` — server unreachable or transport error
  - `InferenceException` — server returned 4xx/5xx
  - `ModelNotFoundException` — model not loaded (404)
  - `InvalidResponseException` — response parsing failed
- 58 tests (40 unit + 18 integration), PHPStan Level 9, PHP-CS-Fixer clean
- GitHub Actions CI: PHP 8.2, 8.3, 8.4
- EUPL-1.2 license
