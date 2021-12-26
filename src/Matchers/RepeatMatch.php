<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Matchers;

use JetBrains\PhpStorm\ArrayShape;
use ZxcvbnPhp\Matcher;
use ZxcvbnPhp\Scorer;

class RepeatMatch extends BaseMatch
{
    public const GREEDY_MATCH = '/(.+)\1+/u';
    public const LAZY_MATCH = '/(.+?)\1+/u';
    public const ANCHORED_LAZY_MATCH = '/^(.+?)\1+$/u';

    public $pattern = 'repeat';

    /**
     * An array of matches for the repeated section itself.
     *
     * @var array<int, BaseMatch>
     */
    public $baseMatches = [];

    /**
     * The number of guesses required for the repeated section itself.
     *
     * @var float
     */
    public $baseGuesses;

    /**
     * The number of times the repeated section is repeated.
     *
     * @var int
     */
    public $repeatCount;

    /**
     * The string that was repeated in the token.
     *
     * @var string
     */
    public $repeatedChar;

    /**
     * Match 3 or more repeated characters.
     *
     * @param string $password
     * @param array<int, string> $userInputs
     * @return RepeatMatch[]
     */
    public static function match(string $password, array $userInputs = []): array
    {
        $matches = [];
        $lastIndex = 0;

        while ($lastIndex < mb_strlen($password)) {
            $greedyMatches = self::findAll($password, self::GREEDY_MATCH, $lastIndex);
            $lazyMatches = self::findAll($password, self::LAZY_MATCH, $lastIndex);

            if (empty($greedyMatches)) {
                break;
            }

            if (mb_strlen($greedyMatches[0][0]['token']) > mb_strlen($lazyMatches[0][0]['token'])) {
                $match = $greedyMatches[0];
                preg_match(self::ANCHORED_LAZY_MATCH, $match[0]['token'], $anchoredMatch);
                $repeatedChar = $anchoredMatch[1];
            } else {
                $match = $lazyMatches[0];
                $repeatedChar = $match[1]['token'];
            }

            $scorer = new Scorer();
            $matcher = new Matcher();

            $baseAnalysis = $scorer->getMostGuessableMatchSequence($repeatedChar, $matcher->getMatches($repeatedChar));
            $baseMatches = $baseAnalysis['sequence'];
            $baseGuesses = $baseAnalysis['guesses'];

            $repeatCount = (int)(mb_strlen($match[0]['token']) / mb_strlen($repeatedChar));

            $matches[] = new static(
                $password,
                $match[0]['begin'],
                $match[0]['end'],
                $match[0]['token'],
                [
                    'repeated_char' => $repeatedChar,
                    'base_guesses'  => $baseGuesses,
                    'base_matches'  => $baseMatches,
                    'repeat_count'  => $repeatCount,
                ]
            );

            $lastIndex = $match[0]['end'] + 1;
        }

        return $matches;
    }

    #[ArrayShape(['warning' => 'string', 'suggestions' => 'string[]'])]
    public function getFeedback(bool $isSoleMatch): array
    {
        $warning = mb_strlen($this->repeatedChar) == 1
            ? 'Repeats like "aaa" are easy to guess'
            : 'Repeats like "abcabcabc" are only slightly harder to guess than "abc"';

        return [
            'warning'     => $warning,
            'suggestions' => [
                'Avoid repeated words and characters',
            ],
        ];
    }

    /**
     * @param string $password
     * @param int $begin
     * @param int $end
     * @param string $token
     * @param array<empty>|array{repeated_char?: string, base_guesses?: float, base_marches?: array<int, BaseMatch>, repeat_count?: int} $params
     */
    public function __construct(string $password, int $begin, int $end, string $token, array $params = [])
    {
        parent::__construct($password, $begin, $end, $token);
        if (!empty($params)) {
            $this->repeatedChar = $params['repeated_char'] ?? '';
            $this->baseGuesses = $params['base_guesses'] ?? 0.0;
            $this->baseMatches = $params['base_matches'] ?? [];
            $this->repeatCount = $params['repeat_count'] ?? 0;
        }
    }

    protected function getRawGuesses(): float
    {
        return $this->baseGuesses * $this->repeatCount;
    }
}
