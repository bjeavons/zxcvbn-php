<?php

/**
 *
 */

namespace ZxcvbnPhp\Matchers;

/**
 * Class Bruteforce
 * @package ZxcvbnPhp\Matchers
 *
 * Intentionally not named with Match suffix to prevent autoloading from Matcher.
 */
class Bruteforce extends Match
{

    /**
     * @copydoc Match::match()
     */
    public static function match($password, array $userInputs = [])
    {
        // Matches entire string.
        $match = new static($password, 0, strlen($password) - 1, $password);
        return [$match];
    }


    public function getFeedback($isSoleMatch)
    {
        return [
            'warning' => "",
            'suggestions' => [
            ]
        ];
    }

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
     *
     */
    public function getEntropy()
    {
        if (is_null($this->entropy)) {
            $this->entropy = $this->log(pow($this->getCardinality(), strlen($this->token)));
        }
        return $this->entropy;
    }

    public function getGuesses()
    {
        // TODO: Implement getGuesses() method.
        return 0;
    }
}
