<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test\Matchers;

use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use ZxcvbnPhp\Matchers\DictionaryMatch;

class DictionaryTest extends AbstractMatchTest
{
    /**
     * @var array<string, mixed>
     */
    protected static array $testDicts = [
        'd1' => [
            'motherboard' => 1,
            'mother' => 2,
            'board' => 3,
            'abcd' => 4,
            'cdef' => 5,
        ],
        'd2' => [
            'z' => 1,
            '8' => 2,
            '99' => 3,
            '$' => 4,
            'asdf1234&*' =>  5,
        ],
    ];

    /**
     * @return Iterator<int, mixed>
     */
    public static function madeUpWordsProvider(): Iterator
    {
        yield ['jjj'];
        yield ['kdncpqw'];
    }

    #[DataProvider('madeUpWordsProvider')]
    public function testWordsNotInDictionary(string $password): void
    {
        $matches = DictionaryMatch::match($password);
        $this->assertEmpty($matches, "does not match non-dictionary words");
    }

    public function testContainingWords(): void
    {
        $password = 'motherboard';
        $patterns = ['mother', 'motherboard', 'board'];

        $this->checkMatches(
            "matches words that contain other words: $password",
            DictionaryMatch::match($password, [], self::$testDicts),
            'dictionary',
            $patterns,
            [[0, 5], [0, 10], [6, 10]],
            [
                'matchedWord' => $patterns,
                'rank' => [2, 1, 3],
                'dictionaryName' => ['d1', 'd1', 'd1'],
            ]
        );
    }

    public function testOverlappingWords(): void
    {
        $password = 'abcdef';
        $patterns = ['abcd', 'cdef'];

        $this->checkMatches(
            "matches multiple words when they overlap",
            DictionaryMatch::match($password, [], self::$testDicts),
            'dictionary',
            $patterns,
            [[0, 3], [2, 5]],
            [
                'matchedWord' => $patterns,
                'rank' => [4, 5],
                'dictionaryName' => ['d1', 'd1', 'd1'],
            ]
        );
    }

    public function testUppercasingIgnored(): void
    {
        $password = 'BoaRdZ';
        $patterns = ['BoaRd', 'Z'];

        $this->checkMatches(
            "ignores uppercasing",
            DictionaryMatch::match($password, [], self::$testDicts),
            'dictionary',
            $patterns,
            [[0, 4], [5, 5]],
            [
                'matchedWord' => ['board', 'z'],
                'rank' => [3, 1],
                'dictionaryName' => ['d1', 'd2'],
            ]
        );
    }

    public function testWordsSurroundedByNonWords(): void
    {
        $prefixes = ['q', '%%'];
        $suffixes = ['%', 'qq'];
        $pattern = 'asdf1234&*';

        foreach ($this->generatePasswords($pattern, $prefixes, $suffixes) as [$password, $i, $j]) {
            $this->checkMatches(
                "identifies words surrounded by non-words",
                DictionaryMatch::match($password, [], self::$testDicts),
                'dictionary',
                [$pattern],
                [[$i, $j]],
                [
                    'matchedWord' => [$pattern],
                    'rank' => [5],
                    'dictionaryName' => ['d2'],
                ]
            );
        }
    }

    public function testAllDictionaryWords(): void
    {
        foreach (self::$testDicts as $dictionaryName => $dict) {
            foreach ($dict as $word => $rank) {
                $word = (string)$word;

                if ($word === 'motherboard') {
                    continue; // skip words that contain others
                }

                $this->checkMatches(
                    "matches against all words in provided dictionaries",
                    DictionaryMatch::match($word, [], self::$testDicts),
                    'dictionary',
                    [$word],
                    [[0, strlen($word) - 1]],
                    [
                        'matchedWord' => [$word],
                        'rank' => [$rank],
                        'dictionaryName' => [$dictionaryName],
                    ]
                );
            }
        }
    }

    public function testDefaultDictionary(): void
    {
        $password = 'wow';
        $patterns = [$password];

        $this->checkMatches(
            "default dictionaries",
            DictionaryMatch::match($password),
            'dictionary',
            $patterns,
            [[0, 2]],
            [
                'matchedWord' => $patterns,
                'rank' => [322],
                'dictionaryName' => ['us_tv_and_film'],
            ]
        );
    }

    public function testUserProvidedInput(): void
    {
        $password = 'foobar';
        $patterns = ['foo', 'bar'];

        $matches = DictionaryMatch::match($password, ['foo', 'bar']);
        $matches = array_values(array_filter($matches, fn($match) => $match->dictionaryName === 'user_inputs'));

        $this->checkMatches(
            "matches with provided user input dictionary",
            $matches,
            'dictionary',
            $patterns,
            [[0, 2], [3, 5]],
            [
                'matchedWord' => ['foo', 'bar'],
                'rank' => [1, 2],
            ]
        );
    }

    public function testUserProvidedInputInNoOtherDictionary(): void
    {
        $password = '39kx9.1x0!3n6';
        $this->checkMatches(
            "matches with provided user input dictionary",
            DictionaryMatch::match($password, [$password]),
            'dictionary',
            [$password],
            [[0, 12]],
            [
                'matchedWord' => [$password],
                'rank' => [1],
            ]
        );
    }

    public function testMatchesInMultipleDictionaries(): void
    {
        $password = 'pass';
        $this->checkMatches(
            "matches words in multiple dictionaries",
            DictionaryMatch::match($password),
            'dictionary',
            ['pass', 'as', 'ass'],
            [[0, 3], [1, 2], [1, 3]],
            [
                'dictionaryName' => ['passwords', 'english_wikipedia', 'us_tv_and_film']
            ]
        );
    }

    public function testGuessesBaseRank(): void
    {
        $match = new DictionaryMatch('aaaaa', 0, 5, 'aaaaaa', ['rank' => 32]);
        $this->assertEqualsWithDelta(32.0, $match->getGuesses(), PHP_FLOAT_EPSILON, "base guesses == the rank");
    }

    public function testGuessesCapitalization(): void
    {
        $match = new DictionaryMatch('AAAaaa', 0, 5, 'AAAaaa', ['rank' => 32]);
        $expected = 32.0 * 41;    // rank * uppercase variations
        $this->assertSame($expected, $match->getGuesses(), "extra guesses are added for capitalization");
    }

    /**
     * @return Iterator<int, mixed>
     */
    public static function uppercaseVariationProvider(): Iterator
    {
        yield [ '',       1 ];
        yield [ 'a',      1 ];
        yield [ 'A',      2 ];
        yield [ 'abcdef', 1 ];
        yield [ 'Abcdef', 2 ];
        yield [ 'abcdeF', 2 ];
        yield [ 'ABCDEF', 2 ];
        yield [ 'aBcdef', 6 ];
        // 6 choose 1
        yield [ 'aBcDef', 21 ];
        // 6 choose 1 + 6 choose 2
        yield [ 'ABCDEf', 6 ];
        // 6 choose 1
        yield [ 'aBCDEf', 21 ];
        // 6 choose 1 + 6 choose 2
        yield [ 'ABCdef', 41 ];
    }

    #[DataProvider('uppercaseVariationProvider')]
    public function testGuessesUppercaseVariations(string $token, float $expectedGuesses): void
    {
        $match = new DictionaryMatch($token, 0, strlen($token) - 1, $token, ['rank' => 1]);
        $this->assertSame(
            $expectedGuesses,
            $match->getGuesses(),
            "guess multiplier of $token is $expectedGuesses"
        );
    }

    public function testFeedbackTop10Password(): void
    {
        $feedback = $this->getFeedbackForToken('password', 'passwords', 2, true);
        $this->assertSame(
            'This is a top-10 common password',
            $feedback['warning'],
            "dictionary match warns about top-10 password"
        );
    }

    public function testFeedbackTop100Password(): void
    {
        $feedback = $this->getFeedbackForToken('hunter', 'passwords', 37, true);
        $this->assertSame(
            'This is a top-100 common password',
            $feedback['warning'],
            "dictionary match warns about top-100 password"
        );
    }

    public function testFeedbackTopPasswordSoleMatch(): void
    {
        $feedback = $this->getFeedbackForToken('mytruck', 'passwords', 19324, true);
        $this->assertSame(
            'This is a very common password',
            $feedback['warning'],
            "dictionary match warns about common password"
        );
    }

    public function testFeedbackTopPasswordNotSoleMatch(): void
    {
        $feedback = $this->getFeedbackForToken('browndog', 'passwords', 7014, false);
        $this->assertSame(
            'This is similar to a commonly used password',
            $feedback['warning'],
            "dictionary match warns about common password (not a sole match)"
        );
    }

    public function testFeedbackTopPasswordNotSoleMatchRankTooLow(): void
    {
        $feedback = $this->getFeedbackForToken('mytruck', 'passwords', 19324, false);
        $this->assertSame(
            '',
            $feedback['warning'],
            "no warning for a non-sole match in the password dictionary"
        );
    }

    public function testFeedbackWikipediaWordSoleMatch(): void
    {
        $feedback = $this->getFeedbackForToken('university', 'english_wikipedia', 69, true);
        $this->assertSame(
            'A word by itself is easy to guess',
            $feedback['warning'],
            "dictionary match warns about Wikipedia word (sole match)"
        );
    }

    public function testFeedbackWikipediaWordNonSoleMatch(): void
    {
        $feedback = $this->getFeedbackForToken('university', 'english_wikipedia', 69, false);
        $this->assertSame(
            '',
            $feedback['warning'],
            "dictionary match doesn't warn about Wikipedia word (not a sole match)"
        );
    }

    public function testFeedbackNameSoleMatch(): void
    {
        $feedback = $this->getFeedbackForToken('rodriguez', 'surnames', 21, true);
        $this->assertSame(
            'Names and surnames by themselves are easy to guess',
            $feedback['warning'],
            "dictionary match warns about surname (sole match)"
        );
    }

    public function testFeedbackNameNonSoleMatch(): void
    {
        $feedback = $this->getFeedbackForToken('rodriguez', 'surnames', 21, false);
        $this->assertSame(
            'Common names and surnames are easy to guess',
            $feedback['warning'],
            "dictionary match warns about surname (not a sole match)"
        );
    }

    public function testFeedbackTvAndFilmDictionary(): void
    {
        $feedback = $this->getFeedbackForToken('know', 'us_tv_and_film', 9, true);
        $this->assertSame(
            '',
            $feedback['warning'],
            "no warning for match from us_tv_and_film dictionary"
        );
    }

    public function testFeedbackAllUppercaseWord(): void
    {
        $feedback = $this->getFeedbackForToken('PASSWORD', 'passwords', 2, true);
        $this->assertContains(
            'All-uppercase is almost as easy to guess as all-lowercase',
            $feedback['suggestions'],
            "dictionary match gives suggestion for all-uppercase word"
        );
    }

    public function testFeedbackWordStartsWithUppercase(): void
    {
        $feedback = $this->getFeedbackForToken('Password', 'passwords', 2, true);
        $this->assertContains(
            'Capitalization doesn\'t help very much',
            $feedback['suggestions'],
            "dictionary match gives suggestion for word starting with uppercase"
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getFeedbackForToken(string $token, string $dictionary, int $rank, bool $soleMatch): array
    {
        $match = new DictionaryMatch($token, 0, strlen($token) - 1, $token, [
            'dictionary_name' => $dictionary,
            'rank' => $rank
        ]);
        return $match->getFeedback($soleMatch);
    }
}
