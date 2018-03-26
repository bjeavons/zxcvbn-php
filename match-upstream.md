# Steps/working notes to make zxcvbn-php match upstream

Goal: Bring zxcvbn-php up to parity with the upstream (CoffeeScript) zxcvbn library, so they always return the same results, and so the PHP port can be easily kept up to date with any future enhancements to the upstream library.

Public API: the main public API of zxcvbn-php does not need to change with this rewrite. Some of the internals will need to change quite a bit though.

Planned Changes:
* Zxcvbn: I updated `passwordStrength()` to more closely mimic upstream's `main.coffee`. I arguably went too far, and might walk that back a bit.
* Feedback: Added.
  * [ ] Need to flesh out based on upstream. Should be pretty straightforward.
* Time estimator: added.
  * [ ] Just need to flesh out `displayTime()` based on upstream. Should be straightforward.
* Matchers: 
  * The structure here is the same as upstream. :-)
  * Individual matchers might or might not match; the best way to find out is with unit tests.
  * [ ] They have a `reverse_dictionary_match` which we don't have. Should be somewhat straightforward as a subclass of `DictionaryMatch`.
  * They have a `regex_match` which seems to be a pure equivalent of `YearMatch`
  * We have `DigitMatch` which they don't. I guess we should get rid of it.
* [ ] `Scorer`:  This is vastly different between upstream and this port. Upstream's algorithm is complicated and hard to follow. I think this will be the hardest thing to bring up to parity. *Some* of this may be similar to `Searcher::getMinimumEntropy()` but I really can't tell.
  * Some of the other language ports e.g. https://github.com/rianhunter/zxcvbn-cpp/blob/zxcvbn-cpp/native-src/zxcvbn/scoring.cpp may also be useful references when porting.
* :question: `ScorerInterface`: In upstream, `scoring.most_guessable_match_sequence` returns a hash with password/guesses/guesses_log10/sequence. Our current `ScorerInterface` has methods for `getScore()` and `getMetrics()`. Our interface is clearly "cleaner", but it might make more sense to just mirror upstream. :neutral_face:
* [ ] `Searcher`: Once we're done using it as a reference when porting `Scorer`, it should probably be deleted.
* [ ] Data files: We have 3 files at `src/Matchers/*.json` which at least approximately correspond to their data files. We should copy their `data/` directory verbatim, and lightly modify their `data-scripts/*.py` to output JSON instead of coffee script.
  * `src/Matchers/adjacency_graphs.json` Based on file size and structure, this looks similar to upstream
  * `src/Matchers/frequency_lists.json` This is structured similarly, but has different datasets.
  * `src/Matchers/ranked_frequency_lists.json` upstream builds this dynamically (see `matching.coffee:5`). Given how PHP handles arrays/dicts I'd be surprised if we need this pre-generated.
* Documentation:
  * [ ] Add phpdoc @see or @link references to upstream methods
* Tests
  * [ ] Upstream's tests are poorly organized, but they're pretty comprehensive. We should find a way to use their test cases, but structure them well like the current tests.

