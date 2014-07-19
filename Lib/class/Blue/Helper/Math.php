<?php
/**
 * contains some helpful mathematics models
 *
 * @package     Core
 * @subpackage  Math
 * @author      chajr   <chajr@bluetree.pl>
 */
namespace Core\Blue\Helper;
class Math
{
    /**
     * Calculates percent difference between two numbers
     * 
     * @param int|float $from
     * @param int|float $to
     * @return int|float
     */
    static function getPercentDifference($from, $to)
    {
        if ($to === 0) {
            return FALSE;
        }

        return 100 - (($to / $from) *100);
    }

    /**
     * calculate with percent is one number of other
     *
     * @param float $part value that is percent of other value
     * @param float $all value to check percent
     * @return integer|boolean return FALSE if $all was 0 value
     */
    static function numberToPercent($part, $all)
    {
        if ($all === 0) {
            return FALSE;
        }

        return ($part / $all) *100;
    }

    /**
     * calculate percent form value
     *
     * @param float $part value that will be percent of other value
     * @param float $all value from calculate percent
     * @return integer
     */
    static function percent($part, $all)
    {
        if ($all === 0) {
            return FALSE;
        }

        return ($part / 100) *$all;
    }

    /**
     * estimate time to end, by given current usage value and max value
     *
     * @param float $edition maximum number of value
     * @param float $used how many was used
     * @param integer $start start time in unix timestamp
     * @return integer estimated end time in unix timestamp
     */
    static function end($edition, $used, $start){
        if (!$used) {
            return 0;
        }

        $timeNow = time();
        $end     = $edition / ($used / ($timeNow - $start));

        $end += $timeNow;
        return $end;
    }
}
