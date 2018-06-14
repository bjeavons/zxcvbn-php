<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\L33tMatch;

class MockL33tMatch extends L33tMatch
{
    protected static function getRankedDictionaries()
    {
        return [
            'words' => [
                'aac' => 1,
                'password' => 3,
                'paassword' => 4,
                'asdf0' => 5,
            ],
            'words2' => [
                'cgo' => 1,
            ]
        ];
    }

    protected static function getL33tTable()
    {
        return array(
            'a' => ['4', '@'],
            'c' => ['(', '{', '[', '<'],
            'g' => ['6', '9'],
            'o' => ['0'],
        );
    }
}

class L33tTest extends AbstractMatchTest
{
    protected $testTable = [
        'a' => ['4', '@'],
        'c' => ['(', '{', '[', '<'],
        'g' => ['6', '9'],
        'o' => ['0'],
    ];

    public function testReducesL33tTable()
    {
        // As this is truly a unit test testing the internals, I'm OK with not implementing
        // it unless it's helpful for resolving discrepancies between the libraries
        $this->markTestSkipped('Not implemented');

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
                L33tMatch::getRelevantL33tSubtable($pw, self::$testTable),
                "reduces l33t table to only the substitutions that a password might be employing"
            );
        }
    }

    public function testEnumeratesL33tSubstitutions()
    {
        // As this is truly a unit test testing the internals, I'm OK with not implementing
        // it unless it's helpful for resolving discrepancies between the libraries
        $this->markTestSkipped('Not implemented');

        // CoffeeScript source: 
        // for [table, subs] in [
        //   [ {},                        [{}] ]
        //   [ {a: ['@']},                [{'@': 'a'}] ]
        //   [ {a: ['@','4']},            [{'@': 'a'}, {'4': 'a'}] ]
        //   [ {a: ['@','4'], c: ['(']},  [{'@': 'a', '(': 'c' }, {'4': 'a', '(': 'c'}] ]
        //   ]
        //   msg = "enumerates the different sets of l33t substitutions a password might be using"
        //   t.deepEquals matching.enumerate_l33t_subs(table), subs, msg
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

    public function commonCaseProvider()
    {
        return array(
            array(
                'password'        => 'p4ssword',
                'pattern'         => 'p4ssword',
                'word'            => 'password',
                'dictionary_name' => 'words',
                'rank'            => 3,
                'ij'              => [0, 7],
                'sub'             => ['4' => 'a']
            ),
            array(
                'password'        => 'p@ssw0rd',
                'pattern'         => 'p@ssw0rd',
                'word'            => 'password',
                'dictionary_name' => 'words',
                'rank'            => 3,
                'ij'              => [0, 7],
                'sub'             => ['@' => 'a', '0' => 'o']
            ),
            array(
                'password'        => 'aSdfO{G0asDfO',
                'pattern'         => '{G0',
                'word'            => 'cgo',
                'dictionary_name' => 'words2',
                'rank'            => 1,
                'ij'              => [5, 7],
                'sub'             => ['{' => 'c', '0' => 'o']
            ),
        );
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
}
