<?php

namespace ZxcvbnPhp\Test\Matchers;

use ReflectionClass;
use ZxcvbnPhp\Matchers\L33tMatch;
use ZxcvbnPhp\Matchers\Match;

class L33tTest extends AbstractMatchTest
{
    protected $testTable = [
        'a' => ['4', '@'],
        'c' => ['(', '{', '[', '<'],
        'g' => ['6', '9'],
        'o' => ['0'],
    ];

    // Generally we only need to test the public interface of the matchers, but it can be useful
    // to occasionally test protected methods to ensure consistency with upstream.
    protected static function callProtectedMethod($name, $args)
    {
        $class = new ReflectionClass('\\ZxcvbnPhp\\Test\\Matchers\\MockL33tMatch');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs(null, $args);
    }

    public function testReducesL33tTable()
    {
        $cases = [
            ''      => [] ,
            'abcdefgo123578!#$&*)]}>' => [] ,
            'a'     => [] ,
            '4'     => [
                'a' => ['4']
            ],
            '4@'    => [
                'a' => ['4', '@']
            ],
            '4({60' => [
                'a' => ['4'],
                'c'   => ['(','{'],
                'g' => ['6'],
                'o' => ['0']
            ],
        ];

        foreach ($cases as $pw => $expected) {
            $this->assertEquals(
                $expected,
                static::callProtectedMethod('getL33tSubtable', [$pw]),
                "reduces l33t table to only the substitutions that a password might be employing"
            );
        }
    }

    public function testEnumeratesL33tSubstitutions()
    {
        $cases = [
            [
                [],
                [[]]
            ],
            [
                ['a' => ['@']],     // subtable
                [['@' => 'a']] ],   // expected result
            [
                ['a' => ['@', '4']],
                [['@' => 'a'], ['4' => 'a']] ],
            [
                ['a' => ['@', '4'], 'c' => ['(']],
                [['@' => 'a', '(' => 'c'], ['4' => 'a', '(' => 'c']]
            ]
        ];

        foreach ($cases as $case) {
            $this->assertEquals(
                $case[1],
                static::callProtectedMethod('getL33tSubstitutions', [$case[0]]),
                "enumerates the different sets of l33t substitutions a password might be using"
            );
        }
    }

    public function testEmptyString()
    {
        $this->assertEquals(
            [],
            MockL33tMatch::match(''),
            "doesn't match empty string"
        );
    }

    public function testPureDictionaryWords()
    {
        $this->assertEquals(
            [],
            MockL33tMatch::match('password'),
            "doesn't match pure dictionary words"
        );
    }

    public function testPureDictionaryWordsWithL33tCharactersAfter()
    {
        $this->assertEquals(
            [],
            MockL33tMatch::match('password4'),
            "doesn't match pure dictionary word with l33t characters after"
        );
    }

    public function commonCaseProvider()
    {
        return [
            [
                'password'        => 'p4ssword',
                'pattern'         => 'p4ssword',
                'word'            => 'password',
                'dictionary_name' => 'words',
                'rank'            => 3,
                'ij'              => [0, 7],
                'sub'             => ['4' => 'a']
            ],
            [
                'password'        => 'p@ssw0rd',
                'pattern'         => 'p@ssw0rd',
                'word'            => 'password',
                'dictionary_name' => 'words',
                'rank'            => 3,
                'ij'              => [0, 7],
                'sub'             => ['@' => 'a', '0' => 'o']
            ],
            [
                'password'        => 'aSdfO{G0asDfO',
                'pattern'         => '{G0',
                'word'            => 'cgo',
                'dictionary_name' => 'words2',
                'rank'            => 1,
                'ij'              => [5, 7],
                'sub'             => ['{' => 'c', '0' => 'o']
            ],
        ];
    }

    /**
     * @dataProvider commonCaseProvider
     * @param string $password
     * @param string $pattern
     * @param string $word
     * @param string $dictionary
     * @param int $rank
     * @param int[] $ij
     * @param array $substitutions
     */
    public function testCommonL33tSubstitutions($password, $pattern, $word, $dictionary, $rank, $ij, $substitutions)
    {
        $this->checkMatches(
            "matches against common l33t substitutions",
            MockL33tMatch::match($password),
            'dictionary',
            [$pattern],
            [$ij],
            [
                'l33t' => [true],
                'sub' => [$substitutions],
                'matchedWord' => [$word],
                'rank' => [$rank],
                'dictionaryName' => [$dictionary]
            ]
        );
    }

    public function testOverlappingL33tPatterns()
    {
        $this->checkMatches(
            "matches against overlapping l33t patterns",
            MockL33tMatch::match('@a(go{G0'),
            'dictionary',
            ['@a(', '(go', '{G0'],
            [[0,2], [2,4], [5,7]],
            [
                'l33t'           => [true, true, true],
                'sub'            => [
                                        ['@' => 'a', '(' => 'c'],
                                        ['(' => 'c'],
                                        ['{' => 'c', '0' => 'o']
                                    ],
                'matchedWord'    => ['aac', 'cgo', 'cgo'],
                'rank'           => [1, 1, 1],
                'dictionaryName' => ['words', 'words2', 'words2'],
            ]
        );
    }

    public function testMultipleL33tSubstitutions()
    {
        $this->assertEquals(
            [],
            MockL33tMatch::match('p4@ssword'),
            "doesn't match when multiple l33t substitutions are needed for the same letter"
        );
    }

    public function testSingleCharacterL33tWords()
    {
        $this->assertEquals(
            [],
            MockL33tMatch::match('4 1 @'),
            "doesn't match single-character l33ted words"
        );
    }

    public function testSubstitutionSubsets()
    {
        // From the coffeescript source:
        //
        // # known issue: subsets of substitutions aren't tried.
        // # for long inputs, trying every subset of every possible substitution could quickly get large,
        // # but there might be a performant way to fix.
        // # (so in this example: {'4': a, '0': 'o'} is detected as a possible sub,
        // # but the subset {'4': 'a'} isn't tried, missing the match for asdf0.)
        // # TODO: consider partially fixing by trying all subsets of size 1 and maybe 2
        // msg = "doesn't match with subsets of possible l33t substitutions"
        // t.deepEqual lm('4sdf0'), [], msg

        $this->assertEquals(
            [],
            MockL33tMatch::match('4sdf0'),
            "doesn't match with subsets of possible l33t substitutions"
        );
    }

    public function testGuessesL33t()
    {
        $match = new L33tMatch('aaa@@@', 0, 5, 'aaa@@@', [
            'rank' => 32,
            'sub' => array('@' => 'a')
        ]);
        $expected = 32 * 41;    // rank * l33t variations
        $this->assertEquals($expected, $match->getGuesses(), "guesses are doubled when word is reversed");
    }

    public function testGuessesL33tAndUppercased()
    {
        $match = new L33tMatch('AaA@@@', 0, 5, 'AaA@@@', [
            'rank' => 32,
            'sub' => array('@' => 'a')
        ]);
        $expected = 32 * 41 * 3;    // rank * l33t variations * uppercase variations
        $this->assertEquals(
            $expected,
            $match->getGuesses(),
            "extra guesses are added for both capitalization and common l33t substitutions"
        );
    }
    
    public function variationsProvider()
    {
        return array(
            [ '',  1, [] ],
            [ 'a', 1, [] ],
            [ '4', 2, ['4' => 'a'] ],
            [ '4pple', 2, ['4' => 'a'] ],
            [ 'abcet', 1, [] ],
            [ '4bcet', 2, ['4' => 'a'] ],
            [ 'a8cet', 2, ['8' => 'b'] ],
            [ 'abce+', 2, ['+' => 't'] ],
            [ '48cet', 4, ['4' => 'a', '8' => 'b'] ],
            [ 'a4a4aa',  Match::binom(6, 2) + Match::binom(6, 1), ['4' => 'a'] ],
            [ '4a4a44',  Match::binom(6, 2) + Match::binom(6, 1), ['4' => 'a'] ],
            [ 'a44att+', (Match::binom(4, 2) + Match::binom(4, 1)) * Match::binom(3, 1), ['4' => 'a', '+' => 't'] ]
        );
    }

    /**
     * @dataProvider variationsProvider
     * @param $token
     * @param $expectedGuesses
     * @param $substitutions
     */
    public function testGuessesL33tVariations($token, $expectedGuesses, $substitutions)
    {
        $match = new L33tMatch($token, 0, strlen($token) - 1, $token, ['rank' => 1, 'sub' => $substitutions]);
        $this->assertEquals(
            $expectedGuesses,
            $match->getGuesses(),
            "extra l33t guesses of $token is $expectedGuesses"
        );
    }

    /**
     * This test is not strictly needed as it's testing an internal detail, but it's included to match an upstream test.
     * @link https://github.com/dropbox/zxcvbn/blob/master/test/test-scoring.coffee#L357
     */
    public function testCapitalisationNotAffectingL33t()
    {
        $token = 'Aa44aA';
        $match = new L33tMatch($token, 0, strlen($token) - 1, $token, ['rank' => 1, 'sub' => ['4' => 'a']]);
        $expected = Match::binom(6, 2) + Match::binom(6, 1);

        $class = new ReflectionClass('\\ZxcvbnPhp\\Matchers\\L33tMatch');
        $method = $class->getMethod('getL33tVariations');
        $method->setAccessible(true);
        $actual = $method->invoke($match);

        $this->assertEquals($expected, $actual, "capitalization doesn't affect extra l33t guesses calc");
    }

    public function testFeedback()
    {
        $token = 'univer5ity';
        $match = new L33tMatch($token, 0, strlen($token) - 1, $token, [
            'dictionary_name' => 'english_wikipedia',
            'rank' => 69,
            'sub' => ['5' => 's'],
        ]);
        $feedback = $match->getFeedback(true);

        $this->assertEquals(
            'A word by itself is easy to guess',
            $feedback['warning'],
            "l33t match didn't lose the original dictionary match warning"
        );
        $this->assertContains(
            'Predictable substitutions like \'@\' instead of \'a\' don\'t help very much',
            $feedback['suggestions'],
            "l33t match gives correct suggestion"
        );
    }

    public function testFeedbackTop100Password()
    {
        $token = 'hunt3r';
        $match = new L33tMatch($token, 0, strlen($token) - 1, $token, [
            'dictionary_name' => 'passwords',
            'rank' => 37,
            'sub' => ['3' => 'e'],
        ]);
        $feedback = $match->getFeedback(true);

        $this->assertEquals(
            'This is similar to a commonly used password',
            $feedback['warning'],
            "l33t match doesn't give top-100 warning"
        );
    }
}
