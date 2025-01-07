<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test;

use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\TimeEstimator;

class TimeEstimatorTest extends TestCase
{
    private TimeEstimator $timeEstimator;

    public function setUp(): void
    {
        $this->timeEstimator = new TimeEstimator();
    }

    public function testTime100PerHour(): void
    {
        $actual = $this->timeEstimator->estimateAttackTimes(100)['crack_times_display']['online_throttling_100_per_hour'];
        $this->assertSame('1 hour', $actual, '100 guesses / 100 per hour = 1 hour');
    }

    public function testTime10PerSecond(): void
    {
        $actual = $this->timeEstimator->estimateAttackTimes(10)['crack_times_display']['online_no_throttling_10_per_second'];
        $this->assertSame('1 second', $actual, '10 guesses / 10 per second = 1 second');
    }

    public function testTime1e4PerSecond(): void
    {
        $actual = $this->timeEstimator->estimateAttackTimes(1e5)['crack_times_display']['offline_slow_hashing_1e4_per_second'];
        $this->assertSame('10 seconds', $actual, '1e5 guesses / 1e4 per second = 10 seconds');
    }

    public function testTime1e10PerSecond(): void
    {
        $actual = $this->timeEstimator->estimateAttackTimes(2e11)['crack_times_display']['offline_fast_hashing_1e10_per_second'];
        $this->assertSame('20 seconds', $actual, '2e11 guesses / 1e10 per second = 20 seconds');
    }

    public function testTimeLessThanASecond(): void
    {
        $actual = $this->timeEstimator->estimateAttackTimes(1)['crack_times_display']['offline_fast_hashing_1e10_per_second'];
        $this->assertSame('less than a second', $actual, 'less than a second');
    }

    public function testTimeCenturies(): void
    {
        $actual = $this->timeEstimator->estimateAttackTimes(1e10)['crack_times_display']['online_throttling_100_per_hour'];
        $this->assertSame('centuries', $actual, 'centuries');
    }

    public function testTimeRounding(): void
    {
        $actual = $this->timeEstimator->estimateAttackTimes(1500)['crack_times_display']['online_no_throttling_10_per_second'];
        $this->assertSame('3 minutes', $actual, '1500 guesses / 10 per second = 3 minutes and not 2.5 minutes');
    }

    public function testPlurals(): void
    {
        $actual = $this->timeEstimator->estimateAttackTimes(12)['crack_times_display']['online_no_throttling_10_per_second'];
        $this->assertSame('1 second', $actual, 'no plural if unit value is 1');

        $actual = $this->timeEstimator->estimateAttackTimes(22)['crack_times_display']['online_no_throttling_10_per_second'];
        $this->assertSame('2 seconds', $actual, 'plural if unit value is more than 1');
    }

    public static function unitProvider(): Iterator
    {
        yield [1e2, '10 seconds'];
        yield [1e3, '2 minutes'];
        yield [1e5, '3 hours'];
        yield [1e7, '12 days'];
        yield [1e8, '4 months'];
        yield [1e9, '3 years'];
    }

    #[DataProvider('unitProvider')]
    public function testTimeUnits(float $guesses, string $displayText): void
    {
        $actual = $this->timeEstimator->estimateAttackTimes($guesses)['crack_times_display']['online_no_throttling_10_per_second'];
        $this->assertSame($displayText, $actual, 'centuries');
    }

    public function testDifferentSpeeds(): void
    {
        $results = $this->timeEstimator->estimateAttackTimes(1e10)['crack_times_seconds'];

        $this->assertSame(1e10 / 1e10, $results['offline_fast_hashing_1e10_per_second']);
        $this->assertSame(1e10 / 1e4, $results['offline_slow_hashing_1e4_per_second']);
        $this->assertSame(1e10 / 10, $results['online_no_throttling_10_per_second']);
        $this->assertSame(1e10 / (100 / 3600), $results['online_throttling_100_per_hour']);
    }

    public function testSpeedLessThanOne(): void
    {
        $actual = $this->timeEstimator->estimateAttackTimes(100)['crack_times_seconds']['offline_slow_hashing_1e4_per_second'];
        $this->assertEqualsWithDelta(0.01, $actual, PHP_FLOAT_EPSILON, 'decimal speed when less than one second');
    }

    public static function scoreProvider(): Iterator
    {
        yield [1e2, 0];
        yield [1e4, 1];
        yield [1e7, 2];
        yield [1e9, 3];
        yield [1e11, 4];
    }

    #[DataProvider('scoreProvider')]
    public function testScores(float $guesses, int $expectedScore): void
    {
        $actual = $this->timeEstimator->estimateAttackTimes($guesses)['score'];
        $this->assertSame($expectedScore, $actual, 'correct score');
    }

    public function testScoreDelta(): void
    {
        $score = $this->timeEstimator->estimateAttackTimes(1000)['score'];
        $this->assertSame(0, $score, 'guesses at threshold gets lower score');

        $score = $this->timeEstimator->estimateAttackTimes(1003)['score'];
        $this->assertSame(0, $score, 'guesses just above threshold gets lower score');

        $score = $this->timeEstimator->estimateAttackTimes(1010)['score'];
        $this->assertSame(1, $score, 'guesses above delta gets higher score');
    }
}
