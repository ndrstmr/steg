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

### Zwischenschritt 1.A — Repository-Konfiguration & Git Setup
**Datum:** 2026-03-23 / 2026-03-24
**Verbrauchte Token (Delta):** ~28k
**Kontext-Usage kumuliert:** ~55% (Session-Mitte)

**Aufgabe:**
Package auf LS-KI-Kontext anpassen, Git-Repository anlegen und pushen, History bereinigen.

**Umsetzung:**
- `composer.json`: PHP `>=8.4`, `symfony/http-client: 7.4.*`, Author mit Email + Homepage, kein `version`-Feld (Packagist-Standard: Git-Tags)
- `phpstan.neon`: `phpVersion: 80400`
- `.github/workflows/ci.yml`: Matrix auf PHP 8.4, Coverage-Job auf 8.4 + `composer:v2`
- README + CHANGELOG + ROADMAP: PHP 8.2/8.3-Referenzen auf 8.4 aktualisiert
- `.gitignore`: PHP-Library-Template (kein Symfony-App-Ballast), `.claude/` granular (Skills tracken, Memory/Settings ignorieren)
- Git init → initialer Commit → `github.com/ndrstmr/steg` gepusht
- `Co-Authored-By`-Zeilen via `git filter-branch` aus History entfernt (Claude nicht als Contributor)
- README: Vollständig auf Englisch, Footer `Built by 👾 public sector dev crew` (kein Dataport/openCode.de)

**CI-Status:** ✅ alle 4 Checks grün nach Anpassungen

---

### Zwischenschritt 1.B — /php Skill Setup
**Datum:** 2026-03-24
**Dauer:** 4:29 min
**Verbrauchte Token (Delta):** ~10k
**Kontext-Usage bei Abschluss:** 68% (136k/200k)

| Kategorie | Tokens | Anteil |
|-----------|--------|--------|
| System prompt | 5.9k | 2.9% |
| System tools | 10.8k | 5.4% |
| Memory files | 1.8k | 0.9% |
| Skills | 0.4k | 0.2% |
| Messages | 117k | 58.5% |
| Free space | 31k | 15.6% |
| Autocompact buffer | 33k | 16.5% |

**Aufgabe:**
PHP-Skill für das Projekt anlegen — als Wissensbasis für alle Entwickler und als
dauerhaften Kontext-Anker für agentische Entwicklung.

**Umsetzung:**
- Skill-Struktur: `.claude/skills/php/SKILL.md` + 5 References
- Basis: `Jeffallan/claude-skills` php-pro Skill, angepasst auf Steg/PHP 8.4/Symfony 7.4
- Laravel-Patterns entfernt → ersetzt durch `steg-patterns.md`
- 5 Reference-Dateien:

| Reference | Inhalt |
|-----------|--------|
| `modern-php-features.md` | PHP 8.4: readonly, enums, fibers, property hooks, asymmetric visibility |
| `symfony-patterns.md` | HttpClient Contracts, SSE Streaming, DI, Bundle-Struktur, ENV-Pattern |
| `steg-patterns.md` | InferenceClientInterface, Value Objects, Exception-Hierarchie, DSN, BC-Rules |
| `async-patterns.md` | SSE Streaming, PHP Fibers, ReactPHP |
| `testing-quality.md` | PHPUnit 11, MockClient, MockHttpClient, PHPStan Level 9, CS-Fixer |

- Skill per `/php` aufrufbar
- `.gitignore` angepasst: `.claude/skills/` wird getrackt, `.claude/memory/` + `settings.local.json` ignoriert

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
- Packagist-Registrierung
- openCode.de Veröffentlichung (wenn bereit)
- CHANGELOG für v1.0.0 finalisieren
- Git-Tag `v1.0.0` setzen

> GitHub Repository `github.com/ndrstmr/steg` ✅ bereits live (Zwischenschritt 1.A)
