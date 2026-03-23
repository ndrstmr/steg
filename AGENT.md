# Steg — Agent Context

## Was ist Steg?

`ndrstmr/steg` ist ein eigenständiges, BC-stabiles PHP-Package als leichtgewichtiger Client für OpenAI-kompatible lokale Inference-Server (vLLM, Ollama, LiteLLM, LocalAI, llama.cpp server).

- **Lizenz:** EUPL-1.2
- **Repository:** github.com/ndrstmr/steg
- **Packagist:** ndrstmr/steg
- **Kontext:** Epic 5.1 im Projekt LS-KI — Portal-KI-Plattform (CCW InnoLab, DL4, Dataport AöR)

---

## Designprinzipien (non-negotiable)

1. **Zero Framework-Dependencies im Core** — nur `psr/log` + `symfony/http-client-contracts`
2. **BC-Promise ab v1.0.0** — im Gegensatz zu symfony/ai-platform (experimentell, kein BC-Promise)
3. **Fokus lokale Endpoints** — vLLM, Ollama, LiteLLM, LocalAI, llama.cpp server
4. **Symfony-Integration optional** — separates Package `ndrstmr/steg-bundle`

---

## Einordnung in die Gesamtarchitektur

Steg ist der **stabile Fallback-Layer** der Portal-KI-Plattform:

```
Portal-KI-Plattform
└── LLM Gateway Interface
    ├── SymfonyAiGateway (symfony/ai-platform v0.6, Generic Bridge) [primär]
    └── StegGateway (ndrstmr/steg, direkter HttpClient) [Fallback]
        └── ENV: LLM_GATEWAY_PROVIDER
```

Infrastruktur-Target: 1× NVIDIA A100-80GB, vLLM mit Llama 3.3 70B AWQ + Mistral Small 3.2

---

## Workflow-Pflicht nach jedem Todo

Nach **jedem** abgeschlossenen Todo zwingend:

1. **README.md** prüfen und anpassen (Versionen, Features, Beispiele)
2. **docs/*.md** prüfen und anpassen
3. **Qualitätschecks** ausführen und alle grün bestätigen:
   ```bash
   composer validate --strict
   vendor/bin/phpstan analyse --no-progress
   vendor/bin/phpunit
   vendor/bin/php-cs-fixer check
   ```
4. **ROADMAP.md** Eintrag schreiben

Kein Todo gilt als abgeschlossen ohne grüne Checks und aktualisierte Docs.

---

## Veröffentlichung

- Packagist: ndrstmr/steg
- GitHub: github.com/ndrstmr/steg
- openCode.de (Dataport OSS-Vermarktung)
