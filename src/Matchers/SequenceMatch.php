<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Matchers;

/** @phpstan-consistent-constructor */
class SequenceMatch extends BaseMatch
{
    public const MAX_DELTA = 5;

    public string $pattern = 'sequence';

    /** @var string The name of the detected sequence. */
    public string $sequenceName;

    /** @var int The number of characters in the complete sequence space. */
    public int $sequenceSpace;

    /** @var bool True if the sequence is ascending, and false if it is descending. */
    public bool $ascending;

    /**
     * @param array{'sequenceName'?: string, 'sequenceSpace'?: int, 'ascending'?: bool} $params
     */
    public function __construct(string $password, int $begin, int $end, string $token, array $params = [])
    {
        parent::__construct($password, $begin, $end, $token);

        $this->sequenceName = $params['sequenceName'] ?? '';
        $this->sequenceSpace = $params['sequenceSpace'] ?? 0;
        $this->ascending = $params['ascending'] ?? false;
    }

    /**
     * Match sequences of three or more characters.
     *
     * @param array<mixed> $userInputs Array of values related to the user (optional)
     *
     * @return array<SequenceMatch>
     */
    public static function match(string $password, array $userInputs = []): array
    {
        $matches = [];
        $passwordLength = mb_strlen($password);

        if ($passwordLength <= 1) {
            return [];
        }

        $begin = 0;
        $lastDelta = null;

        for ($index = 1; $index < $passwordLength; $index++) {
            $delta = mb_ord(mb_substr($password, $index, 1)) - mb_ord(mb_substr($password, $index - 1, 1));
            if ($lastDelta === null) {
                $lastDelta = $delta;
            }
            if ($lastDelta === $delta) {
                continue;
            }

            static::findSequenceMatch($password, $begin, $index - 1, $lastDelta, $matches);
            $begin = $index - 1;
            $lastDelta = $delta;
        }

        static::findSequenceMatch($password, $begin, $passwordLength - 1, $lastDelta, $matches);

        return $matches;
    }

    /**
     * @param array<int, self> $matches
     */
    public static function findSequenceMatch(string $password, int $begin, int $end, int $delta, array &$matches): void
    {
        if ($end - $begin > 1 || abs($delta) === 1) {
            if (abs($delta) > 0 && abs($delta) <= self::MAX_DELTA) {
                $token = mb_substr($password, $begin, $end - $begin + 1);
                if (preg_match('/^[a-z]+$/u', $token)) {
                    $sequenceName = 'lower';
                    $sequenceSpace = 26;
                } elseif (preg_match('/^[A-Z]+$/u', $token)) {
                    $sequenceName = 'upper';
                    $sequenceSpace = 26;
                } elseif (preg_match('/^\d+$/u', $token)) {
                    $sequenceName = 'digits';
                    $sequenceSpace = 10;
                } else {
                    $sequenceName = 'unicode';
                    $sequenceSpace = 26;
                }

                $matches[] = new static($password, $begin, $end, $token, [
                    'sequenceName' => $sequenceName,
                    'sequenceSpace' => $sequenceSpace,
                    'ascending' => $delta > 0,
                ]);
            }
        }
    }

    /**
     * @return array{'warning': string, "suggestions": array<string>}
     */
    public function getFeedback(bool $isSoleMatch): array
    {
        return [
            'warning' => 'Sequences like abc or 6543 are easy to guess',
            'suggestions' => [
                'Avoid sequences',
            ],
        ];
    }

    protected function getRawGuesses(): float
    {
        $firstCharacter = mb_substr((string) $this->token, 0, 1);
        $guesses = 0;

        if (in_array($firstCharacter, ['a', 'A', 'z', 'Z', '0', '1', '9'], true)) {
            $guesses += 4;  // lower guesses for obvious starting points
        } elseif (ctype_digit($firstCharacter)) {
            $guesses += 10; // digits
        } else {
            // could give a higher base for uppercase,
            // assigning 26 to both upper and lower sequences is more conservative
            $guesses += 26;
        }

        if (! $this->ascending) {
            // need to try a descending sequence in addition to every ascending sequence ->
            // 2x guesses
            $guesses *= 2;
        }

        return $guesses * mb_strlen((string) $this->token);
    }
}
