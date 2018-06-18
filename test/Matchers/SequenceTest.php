<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\SequenceMatch;

class SequenceTest extends AbstractMatchTest
{
    public function shortPasswordProvider()
    {
        return [
            [''],
            ['a'],
            ['1'],
        ];
    }

    /**
     * @dataProvider shortPasswordProvider
     * @param $password
     */
    public function testShortPassword($password)
    {
        $matches = SequenceMatch::match($password);
        $this->assertEmpty($matches, "doesn't match length-" . strlen($password) . " sequences");
    }

    public function testNonSequence()
    {
        $password = 'password';
        $matches = SequenceMatch::match($password);
        $this->assertEmpty($matches, "doesn't match password that's not a sequence");
    }

    public function testOverlappingPatterns()
    {
        $password = 'abcbabc';

        $this->checkMatches(
            "matches overlapping patterns",
            SequenceMatch::match($password),
            'sequence',
            ['abc', 'cba', 'abc'],
            [[0, 2], [2, 4], [4, 6]],
            [
                'ascending' => [true, false, true],
            ]
        );
    }

    public function testEmbeddedSequencePatterns()
    {
        $prefixes = ['!', '22'];
        $suffixes = ['!', '22'];
        $pattern = 'jihg';

        foreach ($this->generatePasswords($pattern, $prefixes, $suffixes) as list($password, $i, $j)) {
            $this->checkMatches(
                "matches embedded sequence patterns",
                SequenceMatch::match($password),
                'sequence',
                [$pattern],
                [[$i, $j]],
                [
                    'sequenceName'  => ['lower'],
                    'ascending' => [false],
                ]
            );
        }
    }

    public function sequenceProvider()
    {
        return [
            ['ABC',   'upper',  true],
            ['CBA',   'upper',  false],
            ['PQR',   'upper',  true],
            ['RQP',   'upper',  false],
            ['XYZ',   'upper',  true],
            ['ZYX',   'upper',  false],
            ['abcd',  'lower',  true],
            ['dcba',  'lower',  false],
            ['jihg',  'lower',  false],
            ['wxyz',  'lower',  true],
            ['zxvt',  'lower',  false],
            ['0369',  'digits', true],
            ['97531', 'digits', false]
        ];
    }

    /**
     * @dataProvider sequenceProvider
     * @param string $password
     * @param string $name
     * @param bool $ascending
     */
    public function testSequenceInformation($password, $name, $ascending)
    {
        $this->checkMatches(
            "matches " . $password . " as a " . $name . " sequence",
            SequenceMatch::match($password),
            'sequence',
            [$password],
            [[0, strlen($password) - 1]],
            [
                'sequenceName' => [$name],
                'ascending' => [$ascending],
            ]
        );
    }

    public function testMultipleMatches()
    {
        $password = 'pass123wordZYX';
        $this->checkMatches(
            "matches password with multiple sequences",
            SequenceMatch::match($password),
            'sequence',
            ['123', 'ZYX'],
            [[4, 6], [11, 13]],
            [
                'sequenceName' => ['digits', 'upper'],
                'ascending' => [true, false],
            ]
        );
    }

    public function guessProvider()
    {
        return array(
            array('ab',   true,  4 * 2),        // obvious start * len-2
            array('XYZ',  true,  26 * 3),       // base26 * len-3
            array('4567', true,  10 * 4),       // base10 * len-4
            array('7654', false, 10 * 4 * 2),   // base10 * len-4 * descending
            array('ZYX',  false, 4 * 3 * 2),    // obvious start * len-3 * descending
        );
    }

    /**
     * @dataProvider guessProvider
     * @param $token
     * @param $ascending
     * @param $expectedGuesses
     */
    public function testGuesses($token, $ascending, $expectedGuesses)
    {
        $match = new SequenceMatch($token, 0, strlen($token) - 1, $token, ['ascending' => $ascending]);
        $this->assertEquals(
            $expectedGuesses,
            $match->getGuesses(),
            "the sequence pattern '$token' has guesses of $expectedGuesses"
        );
    }

    public function testEntropy()
    {
        $password = '12345';
        $matches = SequenceMatch::match($password);
        $this->assertEquals(log(5, 2) + 1, $matches[0]->getEntropy());
    }
}
