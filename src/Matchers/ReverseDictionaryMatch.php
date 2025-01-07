<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Matchers;

use ZxcvbnPhp\Matcher;

class ReverseDictionaryMatch extends DictionaryMatch
{
    /** @var bool Whether or not the matched word was reversed in the token. */
    public bool $reversed = true;

    /**
     * Match occurrences of reversed dictionary words in password.
     *
     * @param array<string> $userInputs
     * @param array<string, mixed> $rankedDictionaries
     *
     * @return array<ReverseDictionaryMatch>
     */
    public static function match(string $password, array $userInputs = [], array $rankedDictionaries = []): array
    {
        /** @var array<ReverseDictionaryMatch> $matches */
        $matches = parent::match(self::mbStrRev($password), $userInputs, $rankedDictionaries);
        foreach ($matches as $match) {
            $tempBegin = $match->begin;

            // Change the token, password and [begin, end] values to match the original password
            $match->token = self::mbStrRev($match->token);
            $match->password = self::mbStrRev($match->password);
            $match->begin = mb_strlen($password) - 1 - $match->end;
            $match->end = mb_strlen($password) - 1 - $tempBegin;
        }
        Matcher::usortStable($matches, Matcher::compareMatches(...));
        return $matches;
    }

    /**
     * @return array{'warning': string, "suggestions": array<string>}
     */
    public function getFeedback(bool $isSoleMatch): array
    {
        $feedback = parent::getFeedback($isSoleMatch);

        if (mb_strlen((string) $this->token) >= 4) {
            $feedback['suggestions'][] = "Reversed words aren't much harder to guess";
        }

        return $feedback;
    }

    public static function mbStrRev(string $string, ?string $encoding = null): string
    {
        if ($encoding === null) {
            $encoding = mb_detect_encoding($string);

            if ($encoding === false) {
                $encoding = 'UTF-8';
            }
        }
        $length = mb_strlen($string, $encoding);
        $reversed = '';
        while ($length-- > 0) {
            $reversed .= mb_substr($string, $length, 1, $encoding);
        }

        return $reversed;
    }

    protected function getRawGuesses(): float
    {
        return parent::getRawGuesses() * 2;
    }
}
