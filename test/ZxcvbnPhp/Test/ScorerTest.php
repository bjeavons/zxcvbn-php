<?php

namespace ZxcvbnPhp\Test;

use ZxcvbnPhp\Scorer;

class ScorerTest extends \PHPUnit_Framework_TestCase
{

    public function testCrackTime()
    {
        $this->assertEquals(0.0128, Scorer::crackTime(8), 'Crack time incorrect');
    }

    public function testScore()
    {
        $this->assertEquals(0, Scorer::score(0), 'Score incorrect');
    }
}