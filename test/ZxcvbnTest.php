<?php

namespace ZxcvbnPhp\Test;

use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Zxcvbn;

/**
 * @covers \ZxcvbnPhp\Zxcvbn
 */
class ZxcvbnTest extends TestCase
{
    public function testZxcvbn()
    {
        $zxcvbn = new Zxcvbn();
        $result = $zxcvbn->passwordStrength('');
        $this->assertSame(0, $result['entropy'], 'Entropy incorrect');
        $this->assertSame(0, $result['score'], 'Score incorrect');

        $result = $zxcvbn->passwordStrength('password');
        $this->assertSame(0.0, $result['entropy'], 'Entropy incorrect');
        $this->assertSame(0, $result['score'], 'Score incorrect');

        $result = $zxcvbn->passwordStrength('jjjjj');
        $this->assertSame('repeat', $result['match_sequence'][0]->pattern, 'Pattern incorrect');

        $password = 'abc213456de';
        $result = $zxcvbn->passwordStrength($password);
        $this->assertSame(1, $result['score'], 'Score incorrect');

        $password = '123abcdefgh334123abcdefgh334123abcdefgh334';
        $result = $zxcvbn->passwordStrength($password);
        $this->assertSame(4, $result['score'], 'Score incorrect');

        $password = '3m8dlD.3Y@example.c0m';
        $result = $zxcvbn->passwordStrength($password, [$password]);
        $this->assertSame(0, $result['score'], 'Score incorrect');

        $password = 'bob123';
        $result = $zxcvbn->passwordStrength($password, ['name' => 'bob']);
        $this->assertSame(0, $result['score'], 'Score incorrect');

        $password = 'correct horse battery staple';
        $result = $zxcvbn->passwordStrength($password, ['c']);
        $this->assertSame(4, $result['score'], 'Score incorrect');
    }
}
