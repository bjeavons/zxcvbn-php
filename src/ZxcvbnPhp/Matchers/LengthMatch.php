<?php

namespace ZxcvbnPhp\Matchers;

class LengthMatch extends Match
{

    /**
     * Match occurences of password shorter than 7 characters.
     *
     * @copydoc Match::match()
     */
    public static function match($password)
    {
        $matches = array();
        $length = strlen($password);
        if ($length < 7) {
            $matches[] = new static($password, 0, $length, $password);
        }
        return $matches;
    }

    /**
     * @param $password
     * @param $begin
     * @param $end
     * @param $token
     */
    public function __construct($password, $begin, $end, $token)
    {
        parent::__construct($password, $begin, $end, $token);
        $this->pattern = 'length';
    }

    /**
     * @return float
     */
    public function getEntropy()
    {
        if (is_null($this->entropy)) {
            $this->entropy = $this->log(pow(10, strlen($this->token)));
        }
        return $this->entropy;
    }
}