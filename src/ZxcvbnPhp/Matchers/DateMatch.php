<?php

namespace ZxcvbnPhp\Matchers;

class DateMatch extends Match
{

    /**
     * Match occurences of dates in a password
     *
     * @copydoc Match::match()
     */
    public static function match($password)
    {

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
        $this->pattern = 'date';
    }

    /**
     * @return float
     */
    public function getEntropy()
    {

    }
}