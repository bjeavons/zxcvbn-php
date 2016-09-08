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

        $result = $zxcvbn->passwordStrength("jjjjj");
        $this->assertSame('repeat', $result['match_sequence'][0]->pattern, "Pattern incorrect");

        $password = 'abc213456de';
        $result = $zxcvbn->passwordStrength($password);
        $this->assertEquals(1, $result['score'], "Score incorrect");

        $password = '123abcdefgh334123abcdefgh334123abcdefgh334';
        $result = $zxcvbn->passwordStrength($password);
        $this->assertEquals(4, $result['score'], "Score incorrect");

        $password = '3m8dlD.3Y@example.c0m';
        $result = $zxcvbn->passwordStrength($password, array($password));
        $this->assertEquals(0, $result['score'], "Score incorrect");
    }
}
