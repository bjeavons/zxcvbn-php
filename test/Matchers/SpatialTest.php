<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\BaseMatch;
use ZxcvbnPhp\Matchers\SpatialMatch;

/**
 * @covers \ZxcvbnPhp\Matchers\SpatialMatch
 */
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

    public function testShiftedCountForMultipleMatches()
    {
        $password = "!QAZ1qaz";
        $this->checkMatches(
            "shifted count is correct for two matches in a row",
            SpatialMatch::match($password),
            'spatial',
            ['!QAZ', '1qaz'],
            [[0, 3], [4, 7]],
            [
                'graph' => ['qwerty', 'qwerty'],
                'turns' => [1, 1],
                'shiftedCount' => [4, 0],
            ]
        );
    }

    protected function getBaseGuessCount($token)
    {
        // KEYBOARD_STARTING_POSITIONS * KEYBOARD_AVERAGE_DEGREE * (length - 1)
        // - 1 term because: not counting spatial patterns of length 1
        // eg for length==6, multiplier is 5 for needing to try len2,len3,..,len6
        return SpatialMatch::KEYBOARD_STARTING_POSITION
            * SpatialMatch::KEYBOARD_AVERAGE_DEGREES
            * (strlen($token) - 1);
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
            $this->getBaseGuessCount($token) * (BaseMatch::binom(6, 2) + BaseMatch::binom(6, 1)),
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
            ['6yhgf',        2, 19596.255319547865],
            ['asde3w',       3, 203315.1579961936],
            ['zxcft6yh',     3, 558460.6174911747],
            ['xcvgy7uj',     3, 558460.6174911747],
            ['ertghjm,.',    5, 30160744.32861352],
            ['qwerfdsazxcv', 5, 175281377.64553562],
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
            $expected,
            $match->getGuesses(),
            "spatial guesses accounts for turn positions, directions and starting keys"
        );
    }

    public function testFeedbackStraightLine()
    {
        $token = 'dfghjk';
        $match = new SpatialMatch($token, 0, strlen($token) - 1, $token, [
            'graph' => 'qwerty',
            'turns' => 1,
            'shifted_count' => 0,
        ]);
        $feedback = $match->getFeedback(true);

        $this->assertEquals(
            'Straight rows of keys are easy to guess',
            $feedback['warning'],
            "spatial match in straight line gives correct warning"
        );
        $this->assertContains(
            'Use a longer keyboard pattern with more turns',
            $feedback['suggestions'],
            "spatial match in straight line gives correct suggestion"
        );
    }

    public function testFeedbackWithTurns()
    {
        $token = 'xcvgy789';
        $match = new SpatialMatch($token, 0, strlen($token) - 1, $token, [
            'graph' => 'qwerty',
            'turns' => 3,
            'shifted_count' => 0,
        ]);
        $feedback = $match->getFeedback(true);

        $this->assertEquals(
            'Short keyboard patterns are easy to guess',
            $feedback['warning'],
            "spatial match with turns gives correct warning"
        );
        $this->assertContains(
            'Use a longer keyboard pattern with more turns',
            $feedback['suggestions'],
            "spatial match with turns gives correct suggestion"
        );
    }
}
