<?php

namespace ZxcvbnPhp\Test;

use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Scorer;

/**
 * @covers \ZxcvbnPhp\Scorer
 */
class ScorerTest extends TestCase
{
    public function testScore()
    {
        $scorer = new Scorer();
        $this->assertSame(0, $scorer->score(0), 'Score incorrect');
    }

    public function testCrackTime()
    {
        $scorer = new Scorer();
        $scorer->score(8);
        $metrics = $scorer->getMetrics();
        $this->assertSame(0.0128, $metrics['crack_time'], 'Crack time incorrect');
    }
}
