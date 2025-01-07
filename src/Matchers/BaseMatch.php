<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Matchers;

use ZxcvbnPhp\Math\Binomial;
use ZxcvbnPhp\Scorer;

abstract class BaseMatch implements MatchInterface
{
    public string $pattern = '';

    public function __construct(public string $password, public int $begin, public int $end, public string $token)
    {
    }

    /**
     * Get feedback to a user based on the match.
     *
     * @param  bool $isSoleMatch
     *   Whether this is the only match in the password
     *
     * @return array{'warning': string, "suggestions": array<string>}
     */
    abstract public function getFeedback(bool $isSoleMatch): array;

    /**
     * Find all occurrences of regular expression in a string.
     *
     * @param string $string
     *   String to search.
     * @param string $regex
     *   Regular expression with captures.
     *
     * @return array<int, mixed>
     *   Array of capture groups. Captures in a group have named indexes: 'begin', 'end', 'token'.
     *     e.g. fishfish /(fish)/
     *     array(
     *       array(
     *         array('begin' => 0, 'end' => 3, 'token' => 'fish'),
     *         array('begin' => 0, 'end' => 3, 'token' => 'fish')
     *       ),
     *       array(
     *         array('begin' => 4, 'end' => 7, 'token' => 'fish'),
     *         array('begin' => 4, 'end' => 7, 'token' => 'fish')
     *       )
     *     )
     */
    public static function findAll(string $string, string $regex, int $offset = 0): array
    {
        // $offset is the number of multibyte-aware number of characters to offset, but the offset parameter for
        // preg_match_all counts bytes, not characters: to correct this, we need to calculate the byte offset and pass
        // that in instead.
        $charsBeforeOffset = mb_substr($string, 0, $offset);
        $byteOffset = strlen($charsBeforeOffset);

        $count = preg_match_all($regex, $string, $matches, PREG_SET_ORDER, $byteOffset);
        if (! $count) {
            return [];
        }

        $groups = [];
        foreach ($matches as $group) {
            $captureBegin = 0;
            $match = array_shift($group);
            $matchBegin = mb_strpos($string, (string) $match, $offset);
            $captures = [
                [
                    'begin' => $matchBegin,
                    'end' => $matchBegin + mb_strlen((string) $match) - 1,
                    'token' => $match,
                ],
            ];
            foreach ($group as $capture) {
                $captureBegin = mb_strpos((string) $match, $capture, $captureBegin);
                $captures[] = [
                    'begin' => $matchBegin + $captureBegin,
                    'end' => $matchBegin + $captureBegin + mb_strlen($capture) - 1,
                    'token' => $capture,
                ];
            }
            $groups[] = $captures;
            $offset += mb_strlen((string) $match) - 1;
        }
        return $groups;
    }

    /**
     * Calculate binomial coefficient (n choose k).
     *
     * @deprecated Use {@see Binomial::binom()} instead
     */
    public static function binom(int $n, int $k): float
    {
        return Binomial::binom($n, $k);
    }

    public function getGuesses(): float
    {
        return max($this->getRawGuesses(), $this->getMinimumGuesses());
    }

    public function getGuessesLog10(): float
    {
        return log10($this->getGuesses());
    }

    abstract protected function getRawGuesses(): float;

    protected function getMinimumGuesses(): float
    {
        if (mb_strlen((string) $this->token) < mb_strlen((string) $this->password)) {
            if (mb_strlen((string) $this->token) === 1) {
                return Scorer::MIN_SUBMATCH_GUESSES_SINGLE_CHAR;
            }
            return Scorer::MIN_SUBMATCH_GUESSES_MULTI_CHAR;
        }
        return 0;
    }
}
