# Steg — Development Roadmap

Dieses Dokument protokolliert den Projektfortschritt nach Entwicklungs-Steps.
Jeder Eintrag enthält Aufgabe, Umsetzung, Dauer und Context-Usage als Effizienz-Metrik
für die agentische Software-Entwicklung.

---

## Epic 5.1 — Steg: The Local Inference Bridge for PHP

### Step 1 — Project Briefing & Memory Setup
**Datum:** 2026-03-23
**Kontext-Usage:** ~5% (Briefing, kein Code)

**Aufgabe:**
Epic 5 (LS-KI Phase 5) und Epic 5.1 (Steg Package) lesen, verstehen und dauerhaften
Kontext für alle künftigen Sessions aufbauen.

**Umsetzung:**
- Memory-System initialisiert: `MEMORY.md` Index + 2 Project-Memory-Files
  (`project_steg_epic51.md`, `project_lski_platform.md`)
- `AGENT.md` im Projektverzeichnis als dauerhafter Kontext-Anker erstellt
- Designprinzipien, Dual-Provider-Pattern und OSS-Strategie dokumentiert

**Ergebnis:** Vollständiger Projekt-Kontext für alle Folge-Sessions verfügbar.

---

### Todo 5.1.1 — Steg: Repository-Scaffolding und Core-Interfaces
**Datum:** 2026-03-23
**Dauer:** 10:54 min
**Verbrauchte Token:** 25.3k (Messages: 61.2k gesamt inkl. System/Tools)
**Kontext-Usage bei Abschluss:** 40% (79k/200k)

| Kategorie | Tokens | Anteil |
|-----------|--------|--------|
| System prompt | 5.9k | 2.9% |
| System tools | 10.3k | 5.1% |
| Memory files | 1.8k | 0.9% |
| Skills | 0.4k | 0.2% |
| Messages | 61.2k | 30.6% |
| Free space | 87k | 43.7% |
| Autocompact buffer | 33k | 16.5% |

**Aufgabe:**
Vollständiges Repository-Scaffold für `ndrstmr/steg` erstellen — alle Interfaces,
Value Objects, Client-Implementierungen, Factory, Tests und CI-Konfiguration.

**Umsetzung in 6 Tasks:**

| # | Task | Dateien | Status |
|---|------|---------|--------|
| 1 | Konfigurationsdateien | `composer.json`, `phpunit.xml.dist`, `phpstan.neon`, `.php-cs-fixer.dist.php` | ✅ |
| 2 | Exception-Hierarchie | `StegException`, `ConnectionException`, `InferenceException`, `ModelNotFoundException`, `InvalidResponseException` | ✅ |
| 3 | Value Objects | `ChatMessage`, `CompletionRequest`, `CompletionResponse`, `CompletionOptions`, `ModelInfo`, `StreamChunk` | ✅ |
| 4 | Client-Schicht | `InferenceClientInterface`, `OpenAiCompatibleClient`, `MockClient`, `StegClient` | ✅ |
| 5 | Factory + Tests | `StegClientFactory`, 5× Unit-Tests, 1× Integration-Test | ✅ |
| 6 | Docs + CI + README + LICENSE | GitHub Actions CI, README.md, CHANGELOG.md, LICENSE (EUPL-1.2), 4× docs/*.md | ✅ |

**Fixes nach erstem Durchlauf:**
- PHPStan Level 9: 8 Errors → 0 (Array-Offset-Zugriffe auf `mixed`, `phpstan.neon` deprecated keys)
- PHPUnit: 1 Failure → 0 (Test-Logik für `LogicException` angepasst, da `symfony/http-client` als dev-dep installiert)
- PHP-CS-Fixer: 13 Dateien auto-fixed (kurze Ternary, `static fn`, native function invocation)

**Finale CI-Ergebnisse:**
```
✅ composer validate --strict   → valid
✅ phpstan analyse (Level 9)    → no errors
✅ phpunit                      → 40/40 tests, 74 assertions
✅ php-cs-fixer check           → 0 files to fix
```

**Gesamtstruktur:**
```
src/
├── StegClient.php
├── Client/  InferenceClientInterface, OpenAiCompatibleClient, MockClient
├── Model/   ChatMessage, CompletionRequest, CompletionResponse,
│            CompletionOptions, ModelInfo, StreamChunk
├── Exception/ StegException (abstract) + 4 konkrete Exceptions
└── Factory/ StegClientFactory (DSN + Array-Config)
tests/
├── Unit/    Model/ (3), Client/ (1), Factory/ (1)
└── Integration/ OpenAiCompatibleClientTest (MockHttpClient)
docs/        getting-started, configuration, supported-backends, symfony-integration
.github/workflows/ci.yml  (PHP 8.4, Coverage-Job)
```

---

## Geplante nächste Steps

### Step 3 — OpenAiCompatibleClient: Vollständige Implementierung & Edge Cases
- Streaming-Tests mit `MockHttpClient` (SSE-Format)
- Timeout-Handling vertiefen
- Retry-Logik (optional, konfigurierbar)

### Step 4 — StegClientFactory: Erweiterungen
- DSN-Validierung ausbauen (ungültige Ports, fehlende Hosts)
- Environment-Variable Support (`STEG_DSN`)

### Step 5 — Steg Bundle (ndrstmr/steg-bundle)
- Symfony Bundle Scaffold
- DI Auto-Configuration
- Profiler Panel (Web Debug Toolbar)

### Step 6 — Release Preparation
- `composer.lock` committen
- GitHub Repository anlegen (github.com/ndrstmr/steg)
- Packagist-Registrierung
- openCode.de Veröffentlichung
- CHANGELOG für v1.0.0 finalisieren
