<?php

namespace ZxcvbnPhp\Matchers;

abstract class Match implements MatchInterface
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
     * @var
     */
    public $cardinality;

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
        $this->cardinality = null;
    }

    /**
     * Find matches in a password.
     *
     * @param string $password
     *   Password to check for match.
     * @param array $userInputs
     *   Array of values related to the user (optional).
     * @return array
     *   Array of Match objects
     */
    public static function match($password, array $userInputs = []) {}

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
      *
      */
    public static function findAll($string, $regex, $offset = 0)
    {
        $count = preg_match_all($regex, $string, $matches, PREG_SET_ORDER, $offset);
        if (!$count) {
            return [];
        }

        $pos = 0;
        $groups = [];
        foreach ($matches as $group) {
            $captureBegin = 0;
            $match = array_shift($group);
            $matchBegin = strpos($string, $match, $pos);
            $captures = [
                [
                    'begin' => $matchBegin,
                    'end' => $matchBegin + strlen($match) - 1,
                    'token' => $match,
                ],
            ];
            foreach ($group as $capture) {
                $captureBegin =  strpos($match, $capture, $captureBegin);
                $captures[] = [
                    'begin' => $matchBegin + $captureBegin,
                    'end' => $matchBegin + $captureBegin + strlen($capture) - 1,
                    'token' => $capture,
                ];
            }
            $groups[] = $captures;
            $pos += strlen($match) - 1;
        }
        return $groups;
    }

    /**
     * Get token's symbol space.
     *
     * @return int
     */
    public function getCardinality()
    {
        if (!is_null($this->cardinality)) {
            return $this->cardinality;
        }
        $lower = $upper = $digits = $symbols = $unicode = 0;

        // Use token instead of password to support bruteforce matches on sub-string
        // of password.
        $chars = str_split($this->token);
        foreach ($chars as $char) {
            $ord = ord($char);

            if ($this->isDigit($ord)) {
                $digits = 10;
            }
            elseif ($this->isUpper($ord)) {
                $upper = 26;
            }
            elseif ($this->isLower($ord)) {
                $lower = 26;
            }
            elseif ($this->isSymbol($ord)) {
                $symbols = 33;
            }
            else {
                $unicode = 100;
            }
        }
        $this->cardinality = $lower + $digits + $upper + $symbols + $unicode;
        return $this->cardinality;
    }

    protected function isDigit($ord)
    {
        return $ord >= 0x30 && $ord <= 0x39;
    }

    protected function isUpper($ord)
    {
        return $ord >= 0x41 && $ord <= 0x5a;
    }

    protected function isLower($ord)
    {
        return $ord >= 0x61 && $ord <= 0x7a;
    }

    protected function isSymbol($ord)
    {
        return $ord <= 0x7f;
    }

    /**
     * Calculate entropy.
     *
     * @param $number
     * @return float
     */
    protected function log($number)
    {
        return log($number, 2);
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
    public static function binom($n, $k) {
        $j = $res = 1;

        if($k < 0 || $k > $n) {
            return 0;
        }
        if(($n - $k) < $k) {
            $k = $n - $k;
        }
        while($j <= $k) {
            $res *= $n--;
            $res /= $j++;
        }

        return $res;
    }

    public function getGuessesLog10()
    {
        return log10($this->getGuesses());
    }
}
