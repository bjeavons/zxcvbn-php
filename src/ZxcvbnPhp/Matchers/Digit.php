<?php

namespace ZxcvbnPhp\Matchers;

class Digit extends Match
{

    /**
     * Match occurences of 3 or more digits in a password
     *
     * @copydoc Match::match()
     */
    public static function match($password)
    {
        $matches = array();
        $captures = parent::findAll($password, "/(\d{3,})/");
        foreach ($captures as $capture) {
            $matches[] = new self($password, $capture['begin'], $capture['end'], $capture['token']);
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
        $this->pattern = 'digit';
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