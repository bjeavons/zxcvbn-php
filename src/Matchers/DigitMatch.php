<?php

namespace ZxcvbnPhp\Matchers;

class DigitMatch extends Match
{
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
     * Match occurences of 3 or more digits in a password.
     *
     * @copydoc Match::match()
     *
     * @param       $password
     * @param array $userInputs
     *
     * @return array
     */
    public static function match($password, array $userInputs = [])
    {
        $matches = [];
        $groups = static::findAll($password, '/(\\d{3,})/');
        foreach ($groups as $captures) {
            $matches[] = new static($password, $captures[1]['begin'], $captures[1]['end'], $captures[1]['token']);
        }

        return $matches;
    }

    /**
     * @return float
     */
    public function getEntropy()
    {
        if (null === $this->entropy) {
            $this->entropy = $this->log(10 ** strlen($this->token));
        }

        return $this->entropy;
    }
}
