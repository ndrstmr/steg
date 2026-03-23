<?php

declare(strict_types=1);

namespace Steg\Model;

/**
 * Immutable value object for LLM completion parameters.
 *
 * Use the static factory methods for common presets:
 *   - CompletionOptions::default()        — general purpose (temperature 0.7)
 *   - CompletionOptions::precise()        — structured/JSON output (temperature 0.1)
 *   - CompletionOptions::creative()       — creative text (temperature 0.9)
 *   - CompletionOptions::leichteSprache() — LS-KI default (temperature 0.3)
 */
final readonly class CompletionOptions
{
    public function __construct(
        public readonly float $temperature = 0.7,
        public readonly int $maxTokens = 4096,
        public readonly float $topP = 1.0,
        /** @var list<string>|null */
        public readonly ?array $stop = null,
        public readonly float $frequencyPenalty = 0.0,
        public readonly float $presencePenalty = 0.0,
    ) {
    }

    public static function default(): self
    {
        return new self(
            temperature: 0.7,
            maxTokens: 4096,
        );
    }

    /**
     * Low temperature for structured/JSON outputs.
     */
    public static function precise(): self
    {
        return new self(
            temperature: 0.1,
            maxTokens: 4096,
        );
    }

    /**
     * High temperature for creative text generation.
     */
    public static function creative(): self
    {
        return new self(
            temperature: 0.9,
            maxTokens: 4096,
        );
    }

    /**
     * LS-KI default for Leichte Sprache translations.
     */
    public static function leichteSprache(): self
    {
        return new self(
            temperature: 0.3,
            maxTokens: 4096,
        );
    }

    public function withTemperature(float $temperature): self
    {
        return new self(
            temperature: $temperature,
            maxTokens: $this->maxTokens,
            topP: $this->topP,
            stop: $this->stop,
            frequencyPenalty: $this->frequencyPenalty,
            presencePenalty: $this->presencePenalty,
        );
    }

    public function withMaxTokens(int $maxTokens): self
    {
        return new self(
            temperature: $this->temperature,
            maxTokens: $maxTokens,
            topP: $this->topP,
            stop: $this->stop,
            frequencyPenalty: $this->frequencyPenalty,
            presencePenalty: $this->presencePenalty,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'top_p' => $this->topP,
            'frequency_penalty' => $this->frequencyPenalty,
            'presence_penalty' => $this->presencePenalty,
        ];

        if (null !== $this->stop) {
            $data['stop'] = $this->stop;
        }

        return $data;
    }
}
