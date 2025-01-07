<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\BaseMatch;

class MockMatch extends BaseMatch
{
    public function __construct(int $begin, int $end, protected float $guesses)
    {
        parent::__construct('', $begin, $end, '');
    }

    /**
     * Get feedback to a user based on the match.
     * @param  bool $isSoleMatch
     *   Whether this is the only match in the password
     * @return array{warning: string, suggestions: string[]}
     */
    public function getFeedback(bool $isSoleMatch): array
    {
        return [
            'warning' => '',
            'suggestions' => [],
        ];
    }

    public function getRawGuesses(): float
    {
        return $this->guesses;
    }

    /**
     * Match this password.
     *
     * @param string $password
     *   Password to check for match.
     * @param array<int, string> $userInputs
     *   Array of values related to the user (optional).
     * @code
     *   array('Alice Smith')
     * @endcode
     * @return array<int, mixed>
     *   Array of Match objects
     */
    public static function match(string $password, array $userInputs = []): array
    {
        return [];
    }
}
