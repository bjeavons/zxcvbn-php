<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Matchers;

use ZxcvbnPhp\Matcher;
use ZxcvbnPhp\Scorer;

/** @phpstan-consistent-constructor */
class RepeatMatch extends BaseMatch
{
    public const GREEDY_MATCH = '/(.+)\1+/u';
    public const LAZY_MATCH = '/(.+?)\1+/u';
    public const ANCHORED_LAZY_MATCH = '/^(.+?)\1+$/u';

    public string $pattern = 'repeat';

    /** @var array<MatchInterface> An array of matches for the repeated section itself. */
    public array $baseMatches = [];

    /** @var int The number of guesses required for the repeated section itself. */
    public int $baseGuesses;

    /** @var int The number of times the repeated section is repeated. */
    public int $repeatCount;

    /** @var string The string that was repeated in the token. */
    public string $repeatedChar;

    /**
     * @param array{'repeated_char'?: string, 'base_guesses'?: int, 'base_matches'?: array<mixed>, 'repeat_count'?: int} $params
     */
    public function __construct(string $password, int $begin, int $end, string $token, array $params = [])
    {
        parent::__construct($password, $begin, $end, $token);

        $this->repeatedChar = $params['repeated_char'] ?? '';
        $this->baseGuesses = isset($params['base_guesses']) ? (int) $params['base_guesses'] : 0;
        $this->baseMatches = $params['base_matches'] ?? [];
        $this->repeatCount = isset($params['repeat_count']) ? (int) $params['repeat_count'] : 0;
    }

    /**
     * Match 3 or more repeated characters.
     *
     * @param array<mixed> $userInputs
     *
     * @return array<RepeatMatch>
     */
    public static function match(string $password, array $userInputs = []): array
    {
        $matches = [];
        $lastIndex = 0;

        while ($lastIndex < mb_strlen($password)) {
            $greedyMatches = self::findAll($password, self::GREEDY_MATCH, $lastIndex);
            $lazyMatches = self::findAll($password, self::LAZY_MATCH, $lastIndex);

            if ($greedyMatches === []) {
                break;
            }

            if (mb_strlen((string) $greedyMatches[0][0]['token']) > mb_strlen((string) $lazyMatches[0][0]['token'])) {
                $match = $greedyMatches[0];
                $repeatedChar = '';
                if (preg_match(self::ANCHORED_LAZY_MATCH, (string) $match[0]['token'], $anchoredMatch)) {
                    $repeatedChar = $anchoredMatch[1];
                }
            } else {
                $match = $lazyMatches[0];
                $repeatedChar = $match[1]['token'];
            }

            $scorer = new Scorer();
            $matcher = new Matcher();

            $baseAnalysis = $scorer->getMostGuessableMatchSequence($repeatedChar, $matcher->getMatches($repeatedChar));
            $baseMatches = $baseAnalysis['sequence'];
            $baseGuesses = $baseAnalysis['guesses'];

            $repeatCount = mb_strlen((string) $match[0]['token']) / mb_strlen((string) $repeatedChar);

            $matches[] = new static(
                $password,
                $match[0]['begin'],
                $match[0]['end'],
                $match[0]['token'],
                [
                    'repeated_char' => $repeatedChar,
                    'base_guesses' => $baseGuesses,
                    'base_matches' => $baseMatches,
                    'repeat_count' => $repeatCount,
                ]
            );

            $lastIndex = $match[0]['end'] + 1;
        }

        return $matches;
    }

    /**
     * @return array{'warning': string, "suggestions": array<string>}
     */
    public function getFeedback(bool $isSoleMatch): array
    {
        $warning = mb_strlen($this->repeatedChar) === 1
            ? 'Repeats like "aaa" are easy to guess'
            : 'Repeats like "abcabcabc" are only slightly harder to guess than "abc"';

        return [
            'warning' => $warning,
            'suggestions' => [
                'Avoid repeated words and characters',
            ],
        ];
    }

    protected function getRawGuesses(): float
    {
        return $this->baseGuesses * $this->repeatCount;
    }
}
