<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Matchers;

interface MatchInterface
{
    /**
     * Match this password.
     *
     * @param string $password   Password to check for match
     * @param array<int, string>  $userInputs Array of values related to the user (optional)
     * @code array('Alice Smith')
     * @endcode
     *
     * @return BaseMatch[]
     */
    public static function match(string $password, array $userInputs = []): array;

    public function getGuesses(): float;

    public function getGuessesLog10(): float;
}
