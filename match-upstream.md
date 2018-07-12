# Steps/working notes to make zxcvbn-php match upstream

Goal: Bring zxcvbn-php up to parity with the upstream (CoffeeScript) zxcvbn library, so they always return the same results, and so the PHP port can be easily kept up to date with any future enhancements to the upstream library.

Public API: the main public API of zxcvbn-php does not need to change with this rewrite. Some of the internals will need to change quite a bit though.

Planned Changes:
* Zxcvbn: I updated `passwordStrength()` to more closely mimic upstream's `main.coffee`. I arguably went too far, and might walk that back a bit.
* Feedback: added.
  * [ ] Need to flesh out based on upstream. Should be pretty straightforward.
  * [ ] Write tests for the feedback.
* Time estimator: added.
  * [x] Just need to flesh out `displayTime()` based on upstream. Should be straightforward.
  * [x] Write tests for the time estimator.
* Matchers: added.
  * [x] The majority of the matchers have now been ported.
  * [x] Port the tests for the matchers.
  * [x] RepeatMatch: `base_guesses` and `base_matches` are still missing, but this will require the `Scorer` to be up and running before we can implement them.
* `Scorer`:  This is vastly different between upstream and this port. Upstream's algorithm is complicated and hard to follow. I think this will be the hardest thing to bring up to parity. *Some* of this may be similar to `Searcher::getMinimumEntropy()` but I really can't tell.
  * Some of the other language ports e.g. https://github.com/rianhunter/zxcvbn-cpp/blob/zxcvbn-cpp/native-src/zxcvbn/scoring.cpp may also be useful references when porting. 
  * :question: `ScorerInterface`: In upstream, `scoring.most_guessable_match_sequence` returns a hash with password/guesses/guesses_log10/sequence. Our current `ScorerInterface` has methods for `getScore()` and `getMetrics()`. Our interface is clearly "cleaner", but it might make more sense to just mirror upstream. :neutral_face:
  * [x] Port or rewrite the scorer - this includes returning `guesses`, `guesses_log10` and `score`.
  * [x] Write tests for the scorer.
* [x] `Searcher`: Once we're done using it as a reference when porting `Scorer`, it should probably be deleted.
* [x] Data files: We have 3 files at `src/Matchers/*.json` which at least approximately correspond to their data files. Their `data/` directory has been copied verbatim, and the `data-scripts/*.py` scripts which were generating coffeescript have been modified to output JSON instead. Some of upstream's `data-scripts` are used to build `data/*.txt` files based on wikipedia/wiktionary/etc exports. Those haven't been copied over; instead, if the upstream data files change, we should recopy the data files.
  * [x] `src/Matchers/adjacency_graphs.json` This is identical to upstream.
  * [x] `src/Matchers/frequency_lists.json` This had different datasets, but has now been updated.
  * [x] `src/Matchers/ranked_frequency_lists.json` upstream builds this dynamically (see `matching.coffee:5`), but we don't need it as we're able to use `frequency_lists.json` instead. 
* Documentation:
  * [ ] Add phpdoc @see or @link references to upstream methods

