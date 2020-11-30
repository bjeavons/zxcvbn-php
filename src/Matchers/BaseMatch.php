<?php

namespace ZxcvbnPhp\Matchers;

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

    /**
     * @param $password
     * @param $begin
     * @param $end
     * @param $token
     */
    public function __construct($password, $begin, $end, $token)
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
    abstract public function getFeedback($isSoleMatch);

    /**
     * Find all occurences of regular expression in a string.
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
    public static function findAll($string, $regex, $offset = 0)
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
     * http://www.php.net/manual/en/ref.math.php#57895
     *
     * @param $n
     * @param $k
     * @return int
     */
    public static function binom($n, $k)
    {
        $j = $res = 1;

        if ($k < 0 || $k > $n) {
            return 0;
        }
        if (($n - $k) < $k) {
            $k = $n - $k;
        }
        while ($j <= $k) {
            $res *= $n--;
            $res /= $j++;
        }

        return $res;
    }

    abstract protected function getRawGuesses();

    public function getGuesses()
    {
        return max($this->getRawGuesses(), $this->getMinimumGuesses());
    }

    protected function getMinimumGuesses()
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

    public function getGuessesLog10()
    {
        return log10($this->getGuesses());
    }
}
