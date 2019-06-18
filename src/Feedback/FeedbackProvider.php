<?php declare(strict_types = 1);

namespace ZxcvbnPhp\Feedback;

class FeedbackProvider
{

	private const START_UPPER = '/^[A-Z][^A-Z]+$/';
	private const ALL_UPPER = '/^[^a-z]+$/';

	/**
	 * @var FeedbackMessageProvider
	 */
	private $messageProvider;


	public function __construct(
		FeedbackMessageProvider $messageProvider = NULL
	)
	{
		$this->messageProvider = $messageProvider ?: new StaticFeedbackMessageProvider();
	}


	public function getFeedback(int $score, \ZxcvbnPhp\Matchers\Match ...$matchSequences): Feedback
	{
		if (\count($matchSequences) == 0) {
			return $this->buildFeedback(NULL, ['use_a_few_words', 'no_need_for_mixed_chars']);
		}

		if ($score > 2) {
			return $this->buildFeedback(NULL, []);
		}

		$longestMatch = \reset($matchSequences);

		foreach ($matchSequences as $match) {
			if (\strlen($match->token) > \strlen($longestMatch->token)) {
				$longestMatch = $match;
			}
		}

		$extraFeedback = ['uncommon_words_are_better'];

		try {
			$feedback = $this->getMatchFeedback($longestMatch, \count($matchSequences) == 1);
		} catch (\ZxcvbnPhp\Exception\MatchWithoutFeedbackException $exception) {
			return $this->buildFeedback(NULL, $extraFeedback);
		}

		return $this->buildFeedback($feedback->getWarning(), \array_merge($extraFeedback, $feedback->getSuggestions()));
	}


	private function buildFeedback(?string $warningKey, array $suggestionKeys = []): Feedback
	{
		$suggestions = [];

		foreach ($suggestionKeys as $key) {
			try {
				$suggestions[] = $this->messageProvider->getMessage($key);
			} catch (\ZxcvbnPhp\Exception\FeedbackMessageNotFoundException $exception) {
				continue;
			}
		}
		if ( ! $warningKey) {
			return new Feedback(NULL, $suggestions);
		}

		try {
			$warning = $this->messageProvider->getMessage($warningKey);
		} catch (\ZxcvbnPhp\Exception\FeedbackMessageNotFoundException $exception) {
			$warning = NULL;
		}

		return new Feedback($warning, $suggestions);
	}


	/**
	 * @throws \ZxcvbnPhp\Exception\MatchWithoutFeedbackException
	 */
	private function getMatchFeedback(\ZxcvbnPhp\Matchers\Match $match, bool $isSoleMatch): Feedback
	{
		switch ($match->pattern) {
			case 'dictionary':
				return $this->getDictionaryMatchFeedback($match, $isSoleMatch);

			case 'spatial':
				if ($match instanceof \ZxcvbnPhp\Matchers\SpatialMatch && $match->turns == 1) {
					$warning = 'straight_rows_of_keys_are_easy';
				} else {
					$warning = 'short_keyboard_patterns_are_easy';
				}

				return new Feedback($warning, ['use_longer_keyboard_patterns']);

			case 'sequence':
				return new Feedback('sequences_are_easy',['avoid_sequences']);

			case 'repeat':
				if ($match instanceof \ZxcvbnPhp\Matchers\RepeatMatch && \strlen($match->repeatedChar) == 1) {
					$warning = 'repeated_chars_are_easy';
				} else {
					$warning = 'repeated_patterns_are_easy';
				}

				return new Feedback($warning, ['avoid_repeated_chars']);

			case 'date':
			case 'year':
				return new Feedback('dates_are_easy', ['avoid_associated_dates_and_years']);
		}

		throw new \ZxcvbnPhp\Exception\MatchWithoutFeedbackException();
	}


	private function getDictionaryMatchFeedback(\ZxcvbnPhp\Matchers\Match $match, bool $isSoleMatch): Feedback
	{
		$warning = NULL;
		if ($match instanceof \ZxcvbnPhp\Matchers\DictionaryMatch) {
			$dictionaryName = $match->dictionaryName;
			if (\strpos($dictionaryName, 'passwords')) {
				if ( ! $match instanceof \ZxcvbnPhp\Matchers\L33tMatch) {
					if ($match->rank <= 10) {
						$warning = 'top10_common_password';
					} else if ($match->rank <= 100) {
						$warning = 'top100_common_password';
					} else {
						$warning = 'very_common_password';
					}
				}
			} else if (\strpos($dictionaryName, 'names')) {
				if ($isSoleMatch) {
					$warning = 'names_are_easy';
				} else {
					$warning = 'common_names_are_easy';
				}
			} else if ($dictionaryName === 'user_inputs') {
				$warning = 'found_user_input';
			} else if ($isSoleMatch) {
				$warning = 'a_word_is_easy';
			}
		}

		$suggestions = [];
		$word = $match->token;

		if (\preg_match(self::START_UPPER, $word)) {
			$suggestions[] = 'capitalization_doesnt_help';
		} else if (\preg_match(self::ALL_UPPER, $word) && \strtolower($word) !== $word) {
			$suggestions[] = 'all_uppercase_doesnt_help';
		}

		if ($match instanceof \ZxcvbnPhp\Matchers\L33tMatch) {
			$suggestions[] = 'substitution_doesnt_help';
		}

		return new Feedback($warning, $suggestions);
	}
}
