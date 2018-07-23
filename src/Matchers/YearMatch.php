<?php

namespace ZxcvbnPhp\Matchers;

class YearMatch extends Match
{
    const NUM_YEARS = 119;

    /**
     * @param $password
     * @param $begin
     * @param $end
     * @param $token
     */
    public function __construct($password, $begin, $end, $token)
    {
        parent::__construct($password, $begin, $end, $token);
        $this->pattern = 'year';
    }

    /**
     * Match occurences of years in a password.
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
        $groups = static::findAll($password, '/(19\\d\\d|200\\d|201\\d)/');
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
            $this->entropy = $this->log(self::NUM_YEARS);
        }

        return $this->entropy;
    }
}
