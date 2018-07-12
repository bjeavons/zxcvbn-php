<?php
namespace ZxcvbnPhp\Test;

use ZxcvbnPhp\Feedback;
use ZxcvbnPhp\Matchers\Bruteforce;
use ZxcvbnPhp\Matchers\DateMatch;
use ZxcvbnPhp\Matchers\SequenceMatch;

class FeedbackTest extends \PHPUnit_Framework_TestCase
{
    /** @var Feedback */
    private $feedback;

    public function setUp()
    {
        $this->feedback = new Feedback();
    }

    public function testFeedbackForEmptyPassword()
    {
        $feedback = $this->feedback->getFeedback(0, []);

        $this->assertEquals('', $feedback['warning'], "default warning");
        $this->assertContains(
            'Use a few words, avoid common phrases',
            $feedback['suggestions'],
            "default suggestion #1"
        );
        $this->assertContains(
            'No need for symbols, digits, or uppercase letters',
            $feedback['suggestions'],
            "default suggestion #1"
        );
    }

    public function testHighScoringSequence()
    {
        $match = new Bruteforce('a', 0, 1, 'a');
        $feedback = $this->feedback->getFeedback(3, [$match]);

        $this->assertEquals('', $feedback['warning'], "no warning for good score");
        $this->assertEmpty($feedback['suggestions'], "no suggestions for good score");
    }

    public function testLongestMatchGetsFeedback()
    {
        $match1 = new SequenceMatch('abcd26-01-1991', 0, 4, 'abcd');
        $match2 = new DateMatch('abcd26-01-1991', 4, 14, '26-01-1991', []);
        $feedback = $this->feedback->getFeedback(1, [$match1, $match2]);

        $this->assertEquals(
            'Dates are often easy to guess',
            $feedback['warning'],
            "warning provided for the longest match"
        );
        $this->assertContains(
            'Avoid dates and years that are associated with you',
            $feedback['suggestions'],
            "suggestion provided for the longest match"
        );
        $this->assertNotContains(
            'Avoid sequences',
            $feedback['suggestions'],
            "no suggestion provided for the shorter match"
        );
    }

    public function testDefaultSuggestion()
    {
        $match = new DateMatch('26-01-1991', 0, 10, '26-01-1991', []);
        $feedback = $this->feedback->getFeedback(1, [$match]);

        $this->assertContains(
            'Add another word or two. Uncommon words are better.',
            $feedback['suggestions'],
            "default suggestion provided"
        );
        $this->assertCount(2, $feedback['suggestions'], "default suggestion doesn\'t override existing suggestion");
    }
}
