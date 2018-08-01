<?php

namespace ZxcvbnPhp\Matchers;

use ZxcvbnPhp\Matcher;

class ReverseDictionaryMatch extends DictionaryMatch
{
    /** @var bool Whether or not the matched word was reversed in the token. */
    public $reversed = true;

    /**
     * Match occurences of reversed dictionary words in password.
     *
     * @param $password
     * @param array $userInputs
     * @param array $rankedDictionaries
     * @return ReverseDictionaryMatch[]
     */
    public static function match($password, array $userInputs = [], $rankedDictionaries = [])
    {
        /** @var ReverseDictionaryMatch[] $matches */
        $matches = parent::match(self::mbStrRev($password), $userInputs, $rankedDictionaries);
        foreach ($matches as $match) {
            $tempBegin = $match->begin;

            // Change the token, password and [begin, end] values to match the original password
            $match->token = self::mbStrRev($match->token);
            $match->password = self::mbStrRev($match->password);
            $match->begin = mb_strlen($password) - 1 - $match->end;
            $match->end = mb_strlen($password) - 1 - $tempBegin;
        }
        Matcher::usortStable($matches, [Matcher::class, 'compareMatches']);
        return $matches;
    }

    protected function getRawGuesses()
    {
        return parent::getRawGuesses() * 2;
    }

    public function getFeedback($isSoleMatch)
    {
        $feedback = parent::getFeedback($isSoleMatch);

        if (mb_strlen($this->token) >= 4) {
            $feedback['suggestions'][] = "Reversed words aren't much harder to guess";
        }

        return $feedback;
    }

    public static function mbStrRev($string, $encoding = null)
    {
        if ($encoding === null) {
            $encoding = mb_detect_encoding($string) ?: 'UTF-8';
        }
        $length = mb_strlen($string, $encoding);
        $reversed = '';
        while ($length-- > 0) {
            $reversed .= mb_substr($string, $length, 1, $encoding);
        }

        return $reversed;
    }
}
