<?php

namespace ZxcvbnPhp\Matchers;

interface MatchInterface
{

    /**
     * Match this password.
     *
     * @param string $password
     *   Password to check for match.
     * @return array
     *   Array of Match objects
     */
    public static function match($password);

    /**
     * Get entropy for this match's token.
     *
     * @return float
     *   Entropy of the matched token in the password.
     */
    public function getEntropy();
}