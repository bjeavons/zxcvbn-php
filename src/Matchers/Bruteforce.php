<?php

namespace ZxcvbnPhp\Matchers;

/**
 * Class Bruteforce.
 */
class Bruteforce extends Match
{
    /**
     * @param $password
     * @param $begin
     * @param $end
     * @param $token
     * @param $cardinality
     */
    public function __construct($password, $begin, $end, $token, $cardinality = null)
    {
        parent::__construct($password, $begin, $end, $token);
        $this->pattern = 'bruteforce';
        // Cardinality can be injected to support full password cardinality instead of token.
        $this->cardinality = $cardinality;
    }

    /**
     * @copydoc Match::match()
     *
     * @param       $password
     * @param array $userInputs
     *
     * @return array
     */
    public static function match($password, array $userInputs = [])
    {
        // Matches entire string.
        $match = new static($password, 0, strlen($password) - 1, $password);

        return [$match];
    }

    public function getEntropy()
    {
        if (null === $this->entropy) {
            $this->entropy = $this->log($this->getCardinality() ** strlen($this->token));
        }

        return $this->entropy;
    }
}
