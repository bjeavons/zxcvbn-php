<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\SpatialMatch;

class SpatialTest extends AbstractMatchTest
{
    public function shortPatternDataProvider()
    {
        return array(
            array(''),
            array('/'),
            array('qw'),
            array('*/'),
        );
    }

    /**
     * @dataProvider shortPatternDataProvider
     * @param $password
     */
    public function testShortPatterns($password)
    {
        $this->assertEquals(
            [],
            SpatialMatch::match($password),
            "doesn't match 1- and 2-character spatial patterns"
        );
    }

    public function testNoPattern()
    {
        $this->assertEquals(
            [],
            SpatialMatch::match('qzpm'),
            "doesn't match non-pattern"
        );
    }

    public function testSurroundedPattern()
    {
        $pattern = "6tfGHJ";
        $password = "rz!{$pattern}%z";

        // for testing, make a subgraph that contains a single keyboard
        $graphs = array('qwerty' => SpatialMatch::getAdjacencyGraphs()['qwerty']);

        $this->checkMatches(
            "matches against spatial patterns surrounded by non-spatial patterns",
            SpatialMatch::match($password, array(), $graphs),
            'spatial',
            [$pattern],
            [[3, 8]],
            [
                'graph' => ['qwerty'],
                'turns' => [2],
                'shiftedCount' => [3],
            ]
        );
    }

    public function spatialDataProvider()
    {
        return [
            ['12345',        'qwerty',     1, 0],
            ['@WSX',         'qwerty',     1, 4],
            ['6tfGHJ',       'qwerty',     2, 3],
            ['hGFd',         'qwerty',     1, 2],
            ['/;p09876yhn',  'qwerty',     3, 0],
            ['Xdr%',         'qwerty',     1, 2],
            ['159-',         'keypad',     1, 0],
            ['*84',          'keypad',     1, 0],
            ['/8520',        'keypad',     1, 0],
            ['369',          'keypad',     1, 0],
            ['/963.',        'mac_keypad', 1, 0],
            ['*-632.0214',   'mac_keypad', 9, 0],
            ['aoEP%yIxkjq:', 'dvorak',     4, 5],
            [';qoaOQ:Aoq;a', 'dvorak',    11, 4],
        ];
    }

    /**
     * @dataProvider spatialDataProvider
     * @param $password
     * @param $keyboard
     * @param $turns
     * @param $shifts
     */
    public function testSpatialPatterns($password, $keyboard, $turns, $shifts)
    {
        $graphs = array($keyboard => SpatialMatch::getAdjacencyGraphs()[$keyboard]);

        $this->checkMatches(
            "matches '$password' as a $keyboard pattern",
            SpatialMatch::match($password, array(), $graphs),
            'spatial',
            [$password],
            [[0, strlen($password) - 1]],
            [
                'graph' => [$keyboard],
                'turns' => [$turns],
                'shiftedCount' => [$shifts],
            ]
        );
    }

    public function testMatch()
    {
        $password = 'qzpm';
        $matches = SpatialMatch::match($password);
        $this->assertEmpty($matches);

        $password = 'reds';
        $matches = SpatialMatch::match($password);
        $this->assertCount(1, $matches);

        $password = 'qwerty';
        $matches = SpatialMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame(1, $matches[0]->turns, "Turns incorrect");

        $password = '8qwerty_';
        $matches = SpatialMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame('qwerty', $matches[0]->token, "Token incorrect");

        $password = 'qwER43@!';
        $matches = SpatialMatch::match($password);
        $this->assertCount(2, $matches);
        $this->assertSame('dvorak', $matches[1]->graph, "Graph incorrect");

        $password = 'AOEUIDHG&*()LS_';
        $matches = SpatialMatch::match($password);
        $this->assertCount(2, $matches);
    }

    public function testEntropy()
    {
        $password = 'reds';
        $matches = SpatialMatch::match($password);
        $this->assertEquals(15.23614334369886, $matches[0]->getEntropy());

        // Test shifted character.
        $password = 'rEds';
        $matches = SpatialMatch::match($password);
        $this->assertEquals(17.55807143858622, $matches[0]->getEntropy());
    }
}
