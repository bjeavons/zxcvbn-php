<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\Match;
use ZxcvbnPhp\Matchers\SpatialMatch;

class SpatialTest extends AbstractMatchTest
{
    public function shortPatternDataProvider()
    {
        return [
            [''],
            ['/'],
            ['qw'],
            ['*/'],
        ];
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
        $graphs = ['qwerty' => SpatialMatch::getAdjacencyGraphs()['qwerty']];

        $this->checkMatches(
            "matches against spatial patterns surrounded by non-spatial patterns",
            SpatialMatch::match($password, [], $graphs),
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
        $graphs = [$keyboard => SpatialMatch::getAdjacencyGraphs()[$keyboard]];

        $this->checkMatches(
            "matches '$password' as a $keyboard pattern",
            SpatialMatch::match($password, [], $graphs),
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
    
    protected function getBaseGuessCount($token)
    {
        // KEYBOARD_STARTING_POSITIONS * KEYBOARD_AVERAGE_DEGREE * (length - 1)
        // - 1 term because: not counting spatial patterns of length 1
        // eg for length==6, multiplier is 5 for needing to try len2,len3,..,len6
        return (integer)(SpatialMatch::KEYBOARD_STARTING_POSITION
            * SpatialMatch::KEYBOARD_AVERAGE_DEGREES
            * (strlen($token) - 1)
        );
    }

    public function testGuessesBasic()
    {
        $token = 'zxcvbn';
        $match = new SpatialMatch($token, 0, strlen($token) - 1, $token, [
            'graph' => 'qwerty',
            'turns' => 1,
            'shifted_count' => 0,
        ]);

        $this->assertEquals(
            $this->getBaseGuessCount($token),
            $match->getGuesses(),
            "with no turns or shifts, guesses is starts * degree * (len-1)"
        );
    }

    public function testGuessesShifted()
    {
        $token = 'ZxCvbn';
        $match = new SpatialMatch($token, 0, strlen($token) - 1, $token, [
            'graph' => 'qwerty',
            'turns' => 1,
            'shifted_count' => 2,
        ]);

        $this->assertEquals(
            $this->getBaseGuessCount($token) * (Match::binom(6, 2) + Match::binom(6, 1)),
            $match->getGuesses(),
            "guesses is added for shifted keys, similar to capitals in dictionary matching"
        );
    }

    public function testGuessesEverythingShifted()
    {
        $token = 'ZXCVBN';
        $match = new SpatialMatch($token, 0, strlen($token) - 1, $token, [
            'graph' => 'qwerty',
            'turns' => 1,
            'shifted_count' => 6,
        ]);

        $this->assertEquals(
            $this->getBaseGuessCount($token) * 2,
            $match->getGuesses(),
            "when everything is shifted, guesses are double"
        );
    }

    public function complexGuessProvider()
    {
        return [
            ['6yhgf',        2, 19596.25531914894],
            ['asde3w',       3, 203315.15799004078],
            ['zxcft6yh',     3, 558460.6174739702],
            ['xcvgy7uj',     3, 558460.6174739702],
            ['ertghjm,.',    5, 30160744.327082045],
            ['qwerfdsazxcv', 5, 175281377.63647097],
        ];
    }

    /**
     * @dataProvider complexGuessProvider
     * @param string $token
     * @param int $turns
     * @param float $expected
     */
    public function testGuessesComplexCase($token, $turns, $expected)
    {
        $match = new SpatialMatch($token, 0, strlen($token) - 1, $token, [
            'graph' => 'qwerty',
            'turns' => $turns,
            'shifted_count' => 0,
        ]);

        $this->assertEquals(
            (int)$expected,
            $match->getGuesses(),
            "spatial guesses accounts for turn positions, directions and starting keys"
        );
    }
}
