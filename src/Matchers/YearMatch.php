<?php

namespace ZxcvbnPhp\Matchers;

class YearMatch extends Match
{

    const NUM_YEARS = 119;

    public $pattern = 'year';

    /**
     * Match occurences of years in a password
     *
     * @param string $password
     * @param array $userInputs
     * @return YearMatch[]
     */
    public static function match($password, array $userInputs = [])
    {
        $matches = [];
        $groups = static::findAll($password, "/(19\d\d|200\d|201\d)/");
        foreach ($groups as $captures) {
            $matches[] = new static($password, $captures[1]['begin'], $captures[1]['end'], $captures[1]['token']);
        }
        return $matches;
    }

    public function getFeedback($isSoleMatch)
    {
        return [
            'warning' => "Recent years are easy to guess",
            'suggestions' => [
                'Avoid recent years',
                'Avoid years that are associated with you',
            ]
        ];
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
