<?php

namespace ZxcvbnPhp\Matchers;

class DateMatch extends Match
{

    const NUM_YEARS = 119; // Years match against 1900 - 2019
    const NUM_MONTHS = 12;
    const NUM_DAYS = 31;

    const MIN_YEAR = 1000;
    const MAX_YEAR = 2050;

    const DATE_SPLITS = array(
        4 => array(         # For length-4 strings, eg 1191 or 9111, two ways to split:
            array(1, 2),    # 1 1 91 (2nd split starts at index 1, 3rd at index 2)
            array(2, 3),    # 91 1 1
        ),
        5 => array(
            array(1, 3),    # 1 11 91
            array(2, 3)     # 11 1 91
        ),
        6 => array(
            array(1, 2),    # 1 1 1991
            array(2, 4),    # 11 11 91
            array(4, 5),    # 1991 1 1
        ),
        7 => array(
            array(1, 3),    # 1 11 1991
            array(2, 3),    # 11 1 1991
            array(4, 5),    # 1991 1 11
            array(4, 6),    # 1991 11 1
        ),
        8 => array(
            array(2, 4),    # 11 11 1991
            array(4, 6),    # 1991 11 11
        ),
    );

    const DATE_NO_SEPARATOR = '/^\d{4,8}$/';
    const DATE_WITH_SEPARATOR = '/^'.
      '(\d{1,4})'.       # day, month, year
      '([\s\/\\\\_.-])'. # separator
      '(\d{1,2})'.       # day, month
      '\2'.              # same separator
      '(\d{1,4})'.       # day, month, year
      '$/';

    /**
     * @var
     */
    public $day;

    /**
     * @var
     */
    public $month;

    /**
     * @var
     */
    public $year;

    /**
     * @var
     */
    public $separator;

    /**
     * Match occurences of dates in a password
     *
     * @copydoc Match::match()
     */
    public static function match($password, array $userInputs = array())
    {
        # a "date" is recognized as:
        #   any 3-tuple that starts or ends with a 2- or 4-digit year,
        #   with 2 or 0 separator chars (1.1.91 or 1191),
        #   maybe zero-padded (01-01-91 vs 1-1-91),
        #   a month between 1 and 12,
        #   a day between 1 and 31.
        #
        # note: this isn't true date parsing in that "feb 31st" is allowed,
        # this doesn't check for leap years, etc.
        #
        # recipe:
        # start with regex to find maybe-dates, then attempt to map the integers
        # onto month-day-year to filter the maybe-dates into dates.
        # finally, remove matches that are substrings of other matches to reduce noise.
        #
        # note: instead of using a lazy or greedy regex to find many dates over the full string,
        # this uses a ^...$ regex against every substring of the password -- less performant but leads
        # to every possible date match.
        $matches = array();
        $dates = static::removeRedundantMatches(array_merge(
            static::datesWithoutSeparators($password),
            static::datesWithSeparators($password)
        ));
        foreach ($dates as $date) {
            $matches[] = new static($password, $date['begin'], $date['end'], $date['token'], $date);
        }
        return $matches;
    }

    public function getFeedback($isSoleMatch)
    {
        return array(
            'warning' => "Dates are often easy to guess",
            'suggestions' => array(
                'Avoid dates and years that are associated with you'
            )
        );
    }

    /**
     * @param $password
     * @param $begin
     * @param $end
     * @param $token
     * @param array $params
     *   Array with keys: day, month, year, separator.
     */
    public function __construct($password, $begin, $end, $token, $params)
    {
        parent::__construct($password, $begin, $end, $token);
        $this->pattern = 'date';
        $this->day = $params['day'];
        $this->month = $params['month'];
        $this->year = $params['year'];
        $this->separator = $params['separator'];
    }

    /**
     * Get match entropy.
     *
     * @return float
     */
    public function getEntropy()
    {
        if ($this->year < 100) {
            // two-digit year
            $entropy = $this->log(self::NUM_DAYS * self::NUM_MONTHS * 100);
        }
        else {
            // four-digit year
            $entropy = $this->log(self::NUM_DAYS * self::NUM_MONTHS * self::NUM_YEARS);
        }
        // add two bits for separator selection [/,-,.,etc]
        if (!empty($this->separator)) {
            $entropy += 2;
        }
        return $entropy;
    }

    /**
     * Find dates with separators in a password.
     *
     * @param string $password
     * @return array
     */
    protected static function datesWithSeparators($password)
    {
        $matches = array();
        $length = strlen($password);

        // dates with separators are between length 6 '1/1/91' and 10 '11/11/1991'
        for ($begin = 0; $begin < $length - 5; $begin++) {
            for ($end = $begin + 5; $end - $begin < 10 && $end < $length; $end++) {
                $token = substr($password, $begin, $end - $begin + 1);

                if (!preg_match(static::DATE_WITH_SEPARATOR, $token, $captures)) {
                    continue;
                }

                $date = static::checkDate(array(
                    (integer) $captures[1],
                    (integer) $captures[3],
                    (integer) $captures[4]
                ));

                if ($date === false) {
                    continue;
                }

                $matches[] = array(
                    'begin' => $begin,
                    'end' => $end,
                    'token' => $token,
                    'separator' => $captures[2],
                    'day' => $date['day'],
                    'month' => $date['month'],
                    'year' => $date['year'],
                );
            }
        }

        return $matches;
    }

    /**
     * Find dates without separators in a password.
     *
     * @param string $password
     * @return array
     */
    protected static function datesWithoutSeparators($password)
    {
        $matches = array();
        $length = strlen($password);

        // dates without separators are between length 4 '1191' and 8 '11111991'
        for ($begin = 0; $begin < $length - 3; $begin++) {
            for ($end = $begin + 3; $end - $begin < 8 && $end < $length; $end++) {
                $token = substr($password, $begin, $end - $begin + 1);

                if (!preg_match(static::DATE_NO_SEPARATOR, $token)) {
                    continue;
                }

                $candidates = array();

                $possibleSplits = self::DATE_SPLITS[strlen($token)];
                foreach ($possibleSplits as $splitPositions) {
                    $day = substr($token, 0, $splitPositions[0]);
                    $month = substr($token, $splitPositions[0], $splitPositions[1] - $splitPositions[0]);
                    $year = substr($token, $splitPositions[1]);

                    $date = static::checkDate([$day, $month, $year]);
                    if ($date !== false) {
                        $candidates[] = $date;
                    }
                }

                if (empty($candidates)) {
                    continue;
                }

                // at this point: different possible dmy mappings for the same i,j substring.
                // match the candidate date that likely takes the fewest guesses: a year closest to
                // the current year.
                //
                // ie, considering '111504', prefer 11-15-04 to 1-1-1504
                // (interpreting '04' as 2004)
                $bestCandidate = $candidates[0];
                $minDistance = self::getDistanceForMatch($bestCandidate);

                foreach ($candidates as $candidate) {
                    $distance = self::getDistanceForMatch($candidate);
                    if ($distance < $minDistance) {
                        $bestCandidate = $candidate;
                        $minDistance = $distance;
                    }
                }

                $day = $bestCandidate['day'];
                $month = $bestCandidate['month'];
                $year = $bestCandidate['year'];

                $matches[] = array(
                    'begin' => $begin,
                    'end' => $end,
                    'token' => $token,
                    'separator' => '',
                    'day' => $day,
                    'month' => $month,
                    'year' => $year
                );
            }
        }

        return $matches;
    }

    protected static function getDistanceForMatch($match)
    {
        return abs((integer)$match['year'] - (integer)date('Y'));
    }

    protected static function checkDate($ints)
    {
        // var_dump($ints);die();
        # given a 3-tuple, discard if:
        #   middle int is over 31 (for all dmy formats, years are never allowed in the middle)
        #   middle int is zero
        #   any int is over the max allowable year
        #   any int is over two digits but under the min allowable year
        #   2 ints are over 31, the max allowable day
        #   2 ints are zero
        #   all ints are over 12, the max allowable month
        if ($ints[1] > 31 || $ints[1] <= 0) {
            return false;
        }

        $invalidYear = count(array_filter($ints, function($int) {
            return ($int >= 100 && $int < static::MIN_YEAR)
                || ($int > static::MAX_YEAR);
        }));
        if ($invalidYear > 0) {
            return false;
        }

        $over12 = count(array_filter($ints, function($int) {
            return $int > 12;
        }));
        $over31 = count(array_filter($ints, function($int) {
            return $int > 31;
        }));
        $under1 = count(array_filter($ints, function($int) {
            return $int <= 0;
        }));

        if ($over31 >= 2 || $over12 == 3 || $under1 >= 2) {
            return false;
        }

        # first look for a four digit year: yyyy + daymonth or daymonth + yyyy
        $possibleYearSplits = array(
            array($ints[2], array($ints[0], $ints[1])), // year last
            array($ints[0], array($ints[1], $ints[2])), // year first
        );
        // var_dump($possibleYearSplits);die();
        foreach ($possibleYearSplits as list($year, $rest)) {
            if ($year >= static::MIN_YEAR && $year <= static::MAX_YEAR) {
                if ($dm = static::mapIntsToDayMonth($rest)) {
                    return array(
                        'year'  => $year,
                        'month' => $dm['month'],
                        'day'   => $dm['day'],
                    );
                }
                # for a candidate that includes a four-digit year,
                # when the remaining ints don't match to a day and month,
                # it is not a date.
                return false;
            }
        }

        foreach ($possibleYearSplits as list($year, $rest)) {
            if ($dm = static::mapIntsToDayMonth($rest)) {
                return array(
                    'year'  => static::twoToFourDigitYear($year),
                    'month' => $dm['month'],
                    'day'   => $dm['day'],
                );
            }
        }

        return false;
    }

    protected static function mapIntsToDayMonth($ints)
    {
        foreach(array($ints, array_reverse($ints)) as list($d, $m)) {
            if ($d >= 1 && $d <= 31 && $m >= 1 && $m <= 12) {
                return array(
                    'day'   => $d,
                    'month' => $m
                );
            }
        }

        return false;
    }

    protected static function twoToFourDigitYear($year)
    {
        if ($year > 99) {
            return $year;
        } elseif ($year > 50) {
            // 87 -> 1987
            return $year + 1900;
        } else {
            // 15 -> 2015
            return $year + 2000;
        }

    }

    /**
     * @param array $matches
     * @return array
     */
    protected static function removeRedundantMatches($matches)
    {
        return array_filter($matches, function ($match) use ($matches) {
            foreach ($matches as $otherMatch) {
                if ($match === $otherMatch) {
                    continue;
                }
                if ($otherMatch['begin'] <= $match['begin'] && $otherMatch['end'] >= $match['end']) {
                    return false;
                }
            }

            return true;
        });
    }
}
