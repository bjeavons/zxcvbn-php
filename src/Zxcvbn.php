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
    protected Matcher $matcher;

    protected Scorer $scorer;

    protected TimeEstimator $timeEstimator;

    protected Feedback $feedback;

    public function __construct()
    {
        $this->matcher = new Matcher();
        $this->scorer = new Scorer();
        $this->timeEstimator = new TimeEstimator();
        $this->feedback = new Feedback();
    }

    /**
     * @param class-string $className
     */
    public function addMatcher(string $className): self
    {
        $this->matcher->addMatcher($className);

        return $this;
    }

    /**
     * Calculate password strength via non-overlapping minimum entropy patterns.
     *
     * @param string              $password   Password to measure
     * @param array<int, string>  $userInputs Optional user inputs
     *
     * @return array<string, mixed> Strength result array with keys:
     *               password
     *               entropy
     *               match_sequence
     *               score
     */
    public function passwordStrength(string $password, array $userInputs = []): array
    {
        $timeStart = microtime(true);

        $sanitizedInputs = array_map(
            static fn ($input) => mb_strtolower((string) $input),
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
                'feedback' => $feedback,
                'calc_time' => microtime(true) - $timeStart,
            ]
        );
    }
}
