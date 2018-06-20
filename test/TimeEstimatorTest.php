<?php
namespace ZxcvbnPhp\Test;

use ZxcvbnPhp\TimeEstimator;

class TimeEstimatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var TimeEstimator */
    private $timeEstimator;

    public function setUp()
    {
        $this->timeEstimator = new TimeEstimator();
    }

    public function testTime100PerHour()
    {
        $actual = $this->timeEstimator->estimateAttackTimes(100)['crack_times_display']['online_throttling_100_per_hour'];
        $this->assertEquals('1 hour', $actual, "100 guesses / 100 per hour = 1 hour");
    }

    public function testTime10PerSecond()    {
        $actual = $this->timeEstimator->estimateAttackTimes(10)['crack_times_display']['online_no_throttling_10_per_second'];
        $this->assertEquals('1 second', $actual, "10 guesses / 10 per second = 1 second");
    }

    public function testTime1e4PerSecond()
    {
        $actual = $this->timeEstimator->estimateAttackTimes(1e5)['crack_times_display']['offline_slow_hashing_1e4_per_second'];
        $this->assertEquals('10 seconds', $actual, "1e5 guesses / 1e4 per second = 10 seconds");
    }

    public function testTime1e10PerSecond()
    {
        $actual = $this->timeEstimator->estimateAttackTimes(2e11)['crack_times_display']['offline_fast_hashing_1e10_per_second'];
        $this->assertEquals('20 seconds', $actual, "2e11 guesses / 1e10 per second = 20 seconds");
    }

    public function testTimeLessThanASecond()
    {
        $actual = $this->timeEstimator->estimateAttackTimes(1)['crack_times_display']['offline_fast_hashing_1e10_per_second'];
        $this->assertEquals('less than a second', $actual, "less than a second");
    }

    public function testTimeCenturies()
    {
        $actual = $this->timeEstimator->estimateAttackTimes(1e10)['crack_times_display']['online_throttling_100_per_hour'];
        $this->assertEquals('centuries', $actual, "centuries");
    }

    public function testTimeRounding()
    {
        $actual = $this->timeEstimator->estimateAttackTimes(1500)['crack_times_display']['online_no_throttling_10_per_second'];
        $this->assertEquals('3 minutes', $actual, "1500 guesses / 10 per second = 3 minutes and not 2.5 minutes");
    }

    public function testPlurals()
    {
        $actual = $this->timeEstimator->estimateAttackTimes(12)['crack_times_display']['online_no_throttling_10_per_second'];
        $this->assertEquals('1 second', $actual, "no plural if unit value is 1");

        $actual = $this->timeEstimator->estimateAttackTimes(22)['crack_times_display']['online_no_throttling_10_per_second'];
        $this->assertEquals('2 seconds', $actual, "plural if unit value is more than 1");
    }

    public function unitProvider()
    {
        return [
            [1e2, '10 seconds'],
            [1e3, '2 minutes'],
            [1e5, '3 hours'],
            [1e7, '12 days'],
            [1e8, '4 months'],
            [1e9, '3 years'],
        ];
    }

    /**
     * @dataProvider unitProvider
     * @param int $guesses
     * @param string $displayText
     */
    public function testTimeUnits($guesses, $displayText)
    {
        $actual = $this->timeEstimator->estimateAttackTimes($guesses)['crack_times_display']['online_no_throttling_10_per_second'];
        $this->assertEquals($displayText, $actual, "centuries");
    }

    public function testDifferentSpeeds()
    {
        $results = $this->timeEstimator->estimateAttackTimes(1e10)['crack_times_seconds'];

        $this->assertEquals(1e10 / 1e10, $results['offline_fast_hashing_1e10_per_second']);
        $this->assertEquals(1e10 / 1e4, $results['offline_slow_hashing_1e4_per_second']);
        $this->assertEquals(1e10 / 10, $results['online_no_throttling_10_per_second']);
        $this->assertEquals(1e10 / (100 / 3600), $results['online_throttling_100_per_hour']);
    }

    public function testSpeedLessThanOne()
    {
        $actual = $this->timeEstimator->estimateAttackTimes(100)['crack_times_seconds']['offline_slow_hashing_1e4_per_second'];
        $this->assertEquals(0.01, $actual, "decimal speed when less than one second");
    }

    public function scoreProvider()
    {
        return [
            [1e2, 0],
            [1e4, 1],
            [1e7, 2],
            [1e9, 3],
            [1e11, 4],
        ];
    }

    /**
     * @dataProvider scoreProvider
     * @param int $guesses
     * @param int $expectedScore
     */
    public function testScores($guesses, $expectedScore)
    {
        $actual = $this->timeEstimator->estimateAttackTimes($guesses)['score'];
        $this->assertEquals($expectedScore, $actual, "correct score");
    }

    public function testScoreDelta()
    {
        $score = $this->timeEstimator->estimateAttackTimes(1000)['score'];
        $this->assertEquals(0, $score, "guesses at threshold gets lower score");

        $score = $this->timeEstimator->estimateAttackTimes(1003)['score'];
        $this->assertEquals(0, $score, "guesses just above threshold gets lower score");

        $score = $this->timeEstimator->estimateAttackTimes(1010)['score'];
        $this->assertEquals(1, $score, "guesses above delta gets higher score");
    }
}
