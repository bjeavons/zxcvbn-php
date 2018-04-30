<?php

namespace ZxcvbnPhp\Matchers;

class DateMatch extends Match
{

    const NUM_YEARS = 119; // Years match against 1900 - 2019
    const NUM_MONTHS = 12;
    const NUM_DAYS = 31;

    const MIN_YEAR = 1000;
    const MAX_YEAR = 2050;

    const DATE_NO_SEPARATOR = '/^\d{4,8}$/';
    const DATE_WITH_SEPARATOR = '/^'.
      '(\d{1,4})'.     # day, month, year
      '([\s\/\\_.-])'. # separator
      '(\d{1,2})'.     # day, month
      '\2'.            # same separator
      '(\d{1,4})'.     # day, month, year
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
        // $dates = static::datesWithoutSeparators($password) + static::datesWithSeparators($password);
        $dates = static::datesWithSeparators($password);
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
        $results = array();
        foreach (static::findAll($password, static::DATE_WITH_SEPARATOR) as $captures) {
            $date = static::checkDate(array(
                (integer) $captures[1]['token'],
                (integer) $captures[3]['token'],
                (integer) $captures[4]['token']             
            ));
            if ($date === false) {
                continue;
            }

            $results[] = array(
                'pattern'   => 'date',
                'token'     => $captures[0]['token'],
                'begin'     => $captures[0]['begin'],
                'end'       => $captures[0]['end'],
                'separator' => $captures[2]['token'],
                'year'      => $date['year'],
                'month'     => $date['month'],
                'day'       => $date['day'],
            );
        }

        return $results;
    }

    /**
     * Find dates without separators in a password.
     *
     * @param string $password
     * @return array
     */
    protected static function datesWithoutSeparators($password)
    {
        $dateMatches = array();

        // 1197 is length-4, 01011997 is length 8
        foreach (static::findAll($password, '/(\d{4,8})/') as $captures) {
            $capture = $captures[1];
            $begin = $capture['begin'];
            $end = $capture['end'];

            $token = $capture['token'];
            $tokenLen = strlen($token);

            // Create year candidates.
            $candidates1 = array();
            if ($tokenLen <= 6) {
                // 2 digit year prefix (990112)
                $candidates1[] = array(
                    'daymonth' => substr($token, 2),
                    'year' => substr($token, 0, 2),
                    'begin' => $begin,
                    'end' => $end
                );
                // 2 digit year suffix (011299)
                $candidates1[] = array(
                    'daymonth' => substr($token, 0, ($tokenLen - 2)),
                    'year' => substr($token, -2),
                    'begin' => $begin,
                    'end' => $end
                );
            }
            if ($tokenLen >= 6) {
                // 4 digit year prefix (199912)
                $candidates1[] = array(
                    'daymonth' => substr($token, 4),
                    'year' => substr($token, 0, 4),
                    'begin' => $begin,
                    'end' => $end
                );
                // 4 digit year suffix (121999)
                $candidates1[] = array(
                    'daymonth' => substr($token, 0, ($tokenLen - 4)),
                    'year' => substr($token, -4),
                    'begin' => $begin,
                    'end' => $end
                );
            }
            // Create day/month candidates from years.
            $candidates2 = array();
            foreach ($candidates1 as $candidate) {
                switch (strlen($candidate['daymonth'])) {
                    case 2: // ex. 1 1 97
                        $candidates2[] = array(
                            'day' => $candidate['daymonth'][0],
                            'month' => $candidate['daymonth'][1],
                            'year' => $candidate['year'],
                            'begin' => $candidate['begin'],
                            'end' => $candidate['end']
                        );
                        break;
                    case 3: // ex. 11 1 97 or 1 11 97
                        $candidates2[] = array(
                            'day' => substr($candidate['daymonth'], 0, 2),
                            'month' => substr($candidate['daymonth'], 2),
                            'year' => $candidate['year'],
                            'begin' => $candidate['begin'],
                            'end' => $candidate['end']
                        );
                        $candidates2[] = array(
                            'day' => substr($candidate['daymonth'], 0, 1),
                            'month' => substr($candidate['daymonth'], 1, 3),
                            'year' => $candidate['year'],
                            'begin' => $candidate['begin'],
                            'end' => $candidate['end']
                        );
                        break;
                    case 4: // ex. 11 11 97
                        $candidates2[] = array(
                            'day' => substr($candidate['daymonth'], 0, 2),
                            'month' => substr($candidate['daymonth'], 2, 4),
                            'year' => $candidate['year'],
                            'begin' => $candidate['begin'],
                            'end' => $candidate['end']
                        );
                        break;
                }
            }
            // Reject invalid candidates
            foreach ($candidates2 as $candidate) {
                $day = (integer) $candidate['day'];
                $month = (integer) $candidate['month'];
                $year = (integer) $candidate['year'];

                $date = static::checkDate($day, $month, $year);
                if ($date === false) {
                    continue;
                }
                list($day, $month, $year) = $date;

                $dateMatches[] = array(
                    'begin' => $candidate['begin'],
                    'end' => $candidate['end'],
                    'token' => substr($password, $begin, $begin + $end - 1),
                    'separator' => '',
                    'day' => $day,
                    'month' => $month,
                    'year' => $year
                );
            }
        }
        return $dateMatches;
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
}