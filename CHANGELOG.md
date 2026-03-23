# Changelog

All notable changes to `ndrstmr/steg` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial repository scaffold
- `InferenceClientInterface` — BC-stable contract from v1.0.0
- `OpenAiCompatibleClient` — HTTP client for vLLM, Ollama, LiteLLM, LocalAI
- `MockClient` — deterministic test double with multi-response cycling
- `StegClient` — convenience façade with `ask()`, `chat()`, `complete()`, `stream()`
- `StegClientFactory` — DSN-based and array-config-based factory
- Value Objects: `ChatMessage`, `CompletionRequest`, `CompletionResponse`, `CompletionOptions`, `ModelInfo`, `StreamChunk`
- Exception hierarchy: `StegException` → `ConnectionException`, `InferenceException`, `ModelNotFoundException`, `InvalidResponseException`
- `CompletionOptions` presets: `default()`, `precise()`, `creative()`, `leichteSprache()`
- GitHub Actions CI: PHPUnit + PHPStan Level 9 + PHP-CS-Fixer (PHP 8.4)
- EUPL-1.2 license

## [1.0.0] — TBD

First stable release with BC-promise.
