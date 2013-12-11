<?php

namespace ZxcvbnPhp\Test;

use ZxcvbnPhp\Zxcvbn;

class ZxcvbnTest extends \PHPUnit_Framework_TestCase
{

    public function testZxcvbn()
    {
        $zxcvbn = new Zxcvbn();
        $result = $zxcvbn->passwordStrength("");
        $this->assertEquals(0, $result['entropy'], "Entropy incorrect");
        $this->assertEquals(0, $result['score'], "Score incorrect");

        $result = $zxcvbn->passwordStrength("password");
        $this->assertEquals(0, $result['entropy'], "Entropy incorrect");
        $this->assertEquals(0, $result['score'], "Score incorrect");
    }
}