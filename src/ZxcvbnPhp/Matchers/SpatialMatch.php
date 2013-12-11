<?php

namespace ZxcvbnPhp\Matchers;

/**
 * Class SpatialMatch.
 * @package ZxcvbnPhp\Matchers
 */
class SpatialMatch extends Match
{

    /**
     * @var
     */
    public $shiftedCount;

    /**
     * @var
     */
    public $turns;

    /**
     * Match spatial patterns based on keyboard layouts (e.g. qwerty, dvorak, keypad).
     *
     * @copydoc Match::match()
     */
    public static function match($password)
    {
        $matches = array();
        $graphs = self::getAdjacencyGraphs();
        foreach ($graphs as $name => $graph) {
            $results = self::graphMatch($password, $graph);
            foreach ($results as $result) {
                $result['graph'] = $name;
                $matches[] = new self($password, $result['begin'], $result['end'], $result['token'], $result);
            }
        }
        return $matches;
    }

    /**
     * @param $password
     * @param $begin
     * @param $end
     * @param $token
     * @param array $params
     */
    public function __construct($password, $begin, $end, $token, $params = array())
    {
        parent::__construct($password, $begin, $end, $token);
        $this->pattern = 'spatial';
        if (!empty($params)) {
            $this->shiftedCount = isset($params['shifted_count']) ? $params['shifted_count'] : null;
            $this->turns = isset($params['turns']) ? $params['turns'] : null;
        }
    }

    /**
     * @return float
     */
    public function getEntropy()
    {
        return $this->log($this->rank);

        /**
         $KEYBOARD_AVERAGE_DEGREE = _calc_average_degree( $GRAPHS['qwerty'] );

        # slightly different for keypad/mac keypad, but close enough
        $KEYPAD_AVERAGE_DEGREE = _calc_average_degree( $GRAPHS['keypad'] );

        $KEYBOARD_STARTING_POSITIONS = count( $GRAPHS['qwerty'] );
        $KEYPAD_STARTING_POSITIONS = count( $GRAPHS['keypad'] );

        # on qwerty, 'g' has degree 6, being adjacent to 'ftyhbv'. '\' has degree 1.
        # this calculates the average over all keys.
        function _calc_average_degree( $graph )
            {
            $average = 0.0;

            foreach ( array_values( $graph ) as $neighbors )
            {
            foreach ( $neighbors as $n )
            {
            $list = array();

            if ( ! is_null( $n ) )
            {
            $list[] = $n;
            };

            $average += count( $list );
            };
            };

            $average /= count( $graph );

            return $average;
        };
         */
    }



    /**
     * @param $password
     * @param $graph
     */
    protected static function graphMatch($password, $graph)
    {

    }

    /**
     * Load adjacency graphs.
     *
     * @return array
     */
    protected static function getAdjacencyGraphs()
    {
        $data = file_get_contents(dirname(__FILE__) . '/adjacency_graphs.json');
        return json_decode($data, $assoc = TRUE);
    }
}