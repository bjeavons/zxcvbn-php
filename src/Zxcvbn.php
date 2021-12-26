<?php

declare(strict_types=1);

namespace ZxcvbnPhp;

/**
 * The main entry point.
 *
 * @see  zxcvbn/src/main.coffee
 */
class Zxcvbn
{
    /**
     * @var Matcher
     */
    protected $matcher;

    /**
     * @var Scorer
     */
    protected $scorer;

    /**
     * @var TimeEstimator
     */
    protected $timeEstimator;

    /**
     * @var Feedback
     */
    protected $feedback;

    public function __construct()
    {
        $this->matcher = new \ZxcvbnPhp\Matcher();
        $this->scorer = new \ZxcvbnPhp\Scorer();
        $this->timeEstimator = new \ZxcvbnPhp\TimeEstimator();
        $this->feedback = new \ZxcvbnPhp\Feedback();
    }

    /**
     * @param class-string<\ZxcvbnPhp\Matchers\BaseMatch> $className
     *
     * @return $this
     */
    public function addMatcher(string $className): self
    {
        $this->matcher->addMatcher($className);

        return $this;
    }

    /**
     * Calculate password strength via non-overlapping minimum entropy patterns.
     *
     * @param string   $password   Password to measure
     * @param array<int, string> $userInputs Optional user inputs
     *
     * @return array{calc_time: float, feedback: array<array-key, mixed>, guesses: float, guesses_log10: float, password: string, sequence: array<int, \ZxcvbnPhp\Matchers\BaseMatch>}
     */
    public function passwordStrength(string $password, array $userInputs = []): array
    {
        $timeStart = microtime(true);

        $sanitizedInputs = array_map(
            function ($input) {
                /** @psalm-suppress RedundantCastGivenDocblockType | maybe it's needed here? */
                return mb_strtolower((string) $input);
            },
            $userInputs
        );

        // Get matches for $password.
        // Although the coffeescript upstream sets $sanitizedInputs as a property,
        // doing this immutably makes more sense and is a bit easier
        $matches = $this->matcher->getMatches($password, $sanitizedInputs);

        $result = $this->scorer->getMostGuessableMatchSequence($password, $matches);
        $attackTimes = $this->timeEstimator->estimateAttackTimes($result['guesses']);
        $feedback = $this->feedback->getFeedback($attackTimes['score'], $result['sequence']);

        return array_merge(
            $result,
            $attackTimes,
            [
                'feedback'  => $feedback,
                'calc_time' => microtime(true) - $timeStart
            ]
        );
    }
}
