<?php

namespace ZxcvbnPhp\Matchers;

class YearMatch extends Match
{

    const NUM_YEARS = 119;

    /**
     * Match occurences of years in a password
     *
     * @copydoc Match::match()
     */
    public static function match($password)
    {
        $matches = array();
        $captures = parent::findAll($password, "/(19\d\d|200\d|201\d)/");
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
        $this->pattern = 'year';
    }

    /**
     * @return float
     */
    public function getEntropy()
    {
        if (is_null($this->entropy)) {
            $this->entropy = $this->log(self::NUM_YEARS);
        }
        return $this->entropy;
    }
}