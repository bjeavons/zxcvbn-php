<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Matchers;

use ZxcvbnPhp\Scorer;

final class Bruteforce extends BaseMatch
{
    public const BRUTEFORCE_CARDINALITY = 10;

    public string $pattern = 'bruteforce';

    /**
     * @return array<Bruteforce>
     */
    public static function match(string $password, array $userInputs = []): array
    {
        // Matches entire string.
        $match = new static($password, 0, mb_strlen($password) - 1, $password);
        return [$match];
    }

    /**
     * @return array{'warning': string, "suggestions": array<string>}
     */
    public function getFeedback(bool $isSoleMatch): array
    {
        return [
            'warning' => '',
            'suggestions' => [
            ],
        ];
    }

    public function getRawGuesses(): float
    {
        $guesses = self::BRUTEFORCE_CARDINALITY ** mb_strlen((string) $this->token);
        if ($guesses >= PHP_FLOAT_MAX) {
            return PHP_FLOAT_MAX;
        }

        // small detail: make bruteforce matches at minimum one guess bigger than smallest allowed
        // submatch guesses, such that non-bruteforce submatches over the same [i..j] take precedence.
        if (mb_strlen((string) $this->token) === 1) {
            $minGuesses = Scorer::MIN_SUBMATCH_GUESSES_SINGLE_CHAR + 1;
        } else {
            $minGuesses = Scorer::MIN_SUBMATCH_GUESSES_MULTI_CHAR + 1;
        }

        return max($guesses, $minGuesses);
    }
}
