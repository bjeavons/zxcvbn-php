<?php

namespace ZxcvbnPhp\Matchers;

class Bruteforce extends Match
{

    /**
     * @param string $password
     * @return array
     */
    public static function match($password)
    {
        // Matches entire string.
        $match = new self($password, 0, strlen($password) - 1, $password);
        return array($match);
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
        $this->pattern = 'bruteforce';
    }

    /**
     *
     */
    public function getEntropy()
    {
        if (is_null($this->entropy)) {
            $this->entropy = $this->log(pow($this->getCardinality(), strlen($this->token)));
        }
        return $this->entropy;
    }
}