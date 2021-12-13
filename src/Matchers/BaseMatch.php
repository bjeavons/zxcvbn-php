<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Matchers;

use JetBrains\PhpStorm\ArrayShape;
use ZxcvbnPhp\Scorer;

abstract class BaseMatch implements MatchInterface
{
    /**
     * @var
     */
    public $password;

    /**
     * @var
     */
    public $begin;

    /**
     * @var
     */
    public $end;

    /**
     * @var
     */
    public $token;

    /**
     * @var
     */
    public $pattern;

    public function __construct(string $password, int $begin, int $end, string $token)
    {
        $this->password = $password;
        $this->begin = $begin;
        $this->end = $end;
        $this->token = $token;
    }

    /**
     * Get feedback to a user based on the match.
     *
     * @param  bool $isSoleMatch
     *   Whether this is the only match in the password
     * @return array
     *   Associative array with warning (string) and suggestions (array of strings)
     */
    #[ArrayShape(['warning' => 'string', 'suggestions' => 'string[]'])]
    abstract public function getFeedback(bool $isSoleMatch): array;

    /**
     * Find all occurrences of regular expression in a string.
     *
     * @param string $string
     *   String to search.
     * @param string $regex
     *   Regular expression with captures.
     * @param int $offset
     * @return array
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
        if (!$count) {
            return [];
        }

        $groups = [];
        foreach ($matches as $group) {
            $captureBegin = 0;
            $match = array_shift($group);
            $matchBegin = mb_strpos($string, $match, $offset);
            $captures = [
                [
                    'begin' => $matchBegin,
                    'end' => $matchBegin + mb_strlen($match) - 1,
                    'token' => $match,
                ],
            ];
            foreach ($group as $capture) {
                $captureBegin = mb_strpos($match, $capture, $captureBegin);
                $captures[] = [
                    'begin' => $matchBegin + $captureBegin,
                    'end' => $matchBegin + $captureBegin + mb_strlen($capture) - 1,
                    'token' => $capture,
                ];
            }
            $groups[] = $captures;
            $offset += mb_strlen($match) - 1;
        }
        return $groups;
    }

    /**
     * Calculate binomial coefficient (n choose k).
     *
     * @param int $n
     * @param int $k
     * @return int
     */
    public static function binom(int $n, int $k): int
    {
        if (function_exists('gmp_binomial')) {
            return gmp_intval(gmp_binomial($n, $k));
        }

        return self::binomPolyfill($n, $k);
    }

    /**
     * Substitute for gmp_polynomial for non-negative values of n and k.
     * @param int $n
     * @param int $k
     * @return int
     */
    public static function binomPolyfill(int $n, int $k): int
    {
        if ($k < 0 || $n < 0) {
            throw new \DomainException("n and k must be non-negative");
        }

        if ($k > $n) {
            return 0;
        }

        // $k and $n - $k will always produce the same value, so use smaller of the two
        $k = min($k, $n - $k);

        $c = 1;

        for ($i = 1; $i <= $k; $i++, $n--) {
            // We're aiming for $c * $n / $i, but the $c * $n part could overflow, so use $c / $i * $n instead. The caveat here is that in
            // order to get a precise answer, we need to avoid floats, which means we need to deal with whole part and the remainder
            // separately.
            $c = intdiv($c, $i) * $n + intdiv($c % $i * $n, $i);
        }

        return $c;
    }

    abstract protected function getRawGuesses(): float;

    public function getGuesses(): float
    {
        return max($this->getRawGuesses(), $this->getMinimumGuesses());
    }

    protected function getMinimumGuesses(): float
    {
        if (mb_strlen($this->token) < mb_strlen($this->password)) {
            if (mb_strlen($this->token) === 1) {
                return Scorer::MIN_SUBMATCH_GUESSES_SINGLE_CHAR;
            } else {
                return Scorer::MIN_SUBMATCH_GUESSES_MULTI_CHAR;
            }
        }
        return 0;
    }

    public function getGuessesLog10(): float
    {
        return log10($this->getGuesses());
    }
}
