<?php declare(strict_types = 1);

namespace ZxcvbnPhp\Feedback;

class StaticFeedbackMessageProvider implements FeedbackMessageProvider
{

	/**
	 * @var array
	 */
	private $messages = [
		"use_a_few_words" => 'Použijte více slov, vyhněte se obvyklým frázím',
		"no_need_for_mixed_chars" => 'Nejsou vyžadovány symboly, číslice či velká písmena',
		"uncommon_words_are_better" => 'Přidejte jedno nebo dvě další slova. Čím neobyklejší, tím lépe.',
		"straight_rows_of_keys_are_easy" => 'Posloupnosti sousedních znaků je lehké uhádnout',
		"short_keyboard_patterns_are_easy" => 'Krátké posloupnosti znaků z klávesnice je lehké uhádnout',
		"use_longer_keyboard_patterns" => 'Použijte delší posloupnost stisknutých kláves',
		"repeated_chars_are_easy" => 'Opakování jako "aaa" je lehké uhádnout',
		"repeated_patterns_are_easy" => 'Opakování jako "abcabcabc" jsou jen o málo složitější než "abc"',
		"avoid_repeated_chars" => 'Vyhněte se opakování slov a znaků',
		"sequences_are_easy" => 'Posloupnosti jako "abc" či "6543" lze snadno uhádnout',
		"avoid_sequences" => 'Vyhněte se posloupnostem',
		"recent_years_are_easy" => 'Roky lze snadno uhádnout',
		"avoid_recent_years" => 'Vyhněte se používání roků',
		"avoid_associated_years" => 'Nepoužívejte roky, které lze asociovat s Vámi',
		"dates_are_easy" => 'Datumy lze obvykle snadno uhodnout',
		"avoid_associated_dates_and_years" => 'Nepoužívejte datumy a roky, které lze asociovat s Vámi',
		"top10_common_password" => 'Toto heslo je mezi 10 nejpoužívanějšími',
		"top100_common_password" => 'Toto heslo je mezi 100 nejpoužívanějšími',
		"very_common_password" => 'Toto heslo patří mezi častá hesla',
		"similar_to_common_password" => 'Toto heslo se podobá často používanému heslu',
		"a_word_is_easy" => 'Slovo samo o sobě je velmi lehké na uhádnutí',
		"names_are_easy" => 'Jména a příjmení jsou lehká na uhádnutí',
		"common_names_are_easy" => 'Obvyklá jména a příjmení jsou lehká na uhádnutí',
		"capitalization_doesnt_help" => 'Velká písmena příliš nepomohou',
		"all_uppercase_doesnt_help" => 'Použítí velkých písmen nezlepší bezpečnost hesla',
		"reverse_doesnt_help" => 'Slova pozpátku je lehké uhádnout',
		"substitution_doesnt_help" => 'Běžné substituce jako \'@\' místo \'a\' příliš nezlepší hesla',
	];

	/**
	 * @throws \ZxcvbnPhp\Exception\FeedbackMessageNotFoundException
	 */
	public function getMessage(string $key): string
	{
		if ( ! isset($this->messages[$key])) {
			throw new \ZxcvbnPhp\Exception\FeedbackMessageNotFoundException(
				"Message with key={$key} not found."
			);
		}

		return $this->messages[$key];
	}
}
