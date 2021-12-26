<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test\Matchers;

use JetBrains\PhpStorm\ArrayShape;
use ZxcvbnPhp\Matchers\BaseMatch;

class MockMatch extends BaseMatch
{
    /**
     * @var float
     */
    protected $guesses;

    public function __construct(int $begin, int $end, float $guesses)
    {
        parent::__construct('', $begin, $end, '');
        $this->guesses = $guesses;
    }

    /**
     * Get feedback to a user based on the match.
     * @param  bool $isSoleMatch
     *   Whether this is the only match in the password
     * @return array
     *   Associative array with warning (string) and suggestions (array of strings)
     */
    #[ArrayShape(['warning' => 'string', 'suggestions' => 'string[]'])]
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
     * @return array
     *   Array of Match objects
     */
    public static function match(string $password, array $userInputs = []): array
    {
        return [];
    }
}
