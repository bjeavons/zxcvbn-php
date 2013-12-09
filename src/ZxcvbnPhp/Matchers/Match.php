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
    public $entropy;

    /**
     * @var
     */
    protected $cardinality;

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
        $this->entropy = null;
        $this->cardinality = null;
    }

    /**
     * @param string $password
     * @return array
     *   Array of Match objects
     */
    public static function match($password) {}

    /**
     * @return float
     *   Entropy of the matched token in the password.
     */
    public function getEntropy() {}

    /**
      * Find all occurences of regular expression in a string.
      *
      * @param string $string
      *   String to search.
      * @param string $regex
      *   Regular expression with captures.
      * @return array
      *   Array of captures with named indexes.
      */
    public static function findAll($string, $regex)
    {
        $captures = array();
        preg_match_all($regex, $string, $matches, PREG_OFFSET_CAPTURE);

        if (isset($matches[1])) {
            foreach ($matches[1] as $capture) {
                list($token, $begin) = $capture;
                $captures[] = array(
                    'begin' => $begin,
                    'end' => $begin + strlen($token),
                    'token' => $token,
                );
            }
        }
        return $captures;
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
            elseif ($this->isLower($ord)) {
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
}