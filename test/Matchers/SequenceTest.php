<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\SequenceMatch;

class SequenceTest extends AbstractMatchTest
{
    public function shortPasswordProvider()
    {
        return array(
            array(''),
            array('a'),
            array('1'),
        );
    }

    /**
     * @dataProvider shortPasswordProvider
     * @param $password
     */
    public function testDoesNotMatchLessThanTwoCharacters($password)
    {
        $matches = SequenceMatch::match($password);
        $this->assertEmpty($matches, "doesn't match length-" . strlen($password) . " sequences");
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
        $prefixes = array('!', '22');
        $suffixes = array('!', '22');
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
        return array(
            array('ABC',   'upper',  true),
            array('CBA',   'upper',  false),
            array('PQR',   'upper',  true),
            array('RQP',   'upper',  false),
            array('XYZ',   'upper',  true),
            array('ZYX',   'upper',  false),
            array('abcd',  'lower',  true),
            array('dcba',  'lower',  false),
            array('jihg',  'lower',  false),
            array('wxyz',  'lower',  true),
            array('zxvt',  'lower',  false),
            array('0369',  'digits', true),
            array('97531', 'digits', false)
        );
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

    public function testMatch()
    {
        $password = 'password';
        $matches = SequenceMatch::match($password);
        $this->assertEmpty($matches);

        $password = '12ab78UV';
        $matches = SequenceMatch::match($password);
        $this->assertEmpty($matches);

        $password = '12345';
        $matches = SequenceMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame($password, $matches[0]->token, "Token incorrect");
        $this->assertSame($password, $matches[0]->password, "Password incorrect");

        $password = 'ZYX';
        $matches = SequenceMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame($password, $matches[0]->token, "Token incorrect");
        $this->assertSame($password, $matches[0]->password, "Password incorrect");

        $password = 'pass123wordZYX';
        $matches = SequenceMatch::match($password);
        $this->assertCount(2, $matches);
        $this->assertSame('123', $matches[0]->token, "First match token incorrect");
        $this->assertSame('ZYX', $matches[1]->token, "Second match token incorrect");

        $password = 'wordZYX ';
        $matches = SequenceMatch::match($password);
        $this->assertEquals('ZYX', $matches[0]->token, "First match token incorrect");

        $password = 'XYZ123 ';
        $matches = SequenceMatch::match($password);
        $this->assertEquals('XYZ', $matches[0]->token, "First match token incorrect");
        $this->assertEquals('123', $matches[1]->token, "Second match token incorrect");

        $password = 'abc213456de';
        $matches = SequenceMatch::match($password);
        $this->assertEquals('abc', $matches[0]->token, "First match token incorrect");
        $this->assertEquals('3456', $matches[1]->token, "Second match token incorrect");
    }

    public function testEntropy()
    {
        $password = '12345';
        $matches = SequenceMatch::match($password);
        $this->assertEquals(log(5, 2) + 1, $matches[0]->getEntropy());
    }
}
