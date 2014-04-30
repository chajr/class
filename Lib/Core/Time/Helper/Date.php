<?php
/**
 * allow to manipulate date as object
 *
 * @package     Core
 * @subpackage  Date
 * @author      chajr   <chajr@bluetree.pl>
 */
class Core_Time_Helper_Date
{
    /**
     * contains unix timestamp
     * @var integer
     */
    private $_unixTimestamp = 0;

    /**
     * error information
     * @var string
     */
    public $err;

    /**
     * inform to use convert method from Core_Time_Helper_Simple_Date to fix text (default FALSE)
     * @var boolean 
     */
    public $useConversion = FALSE;

    /**
     * list of conversion types, default from ISO-8859-2 to UTF-8
     * @var array
     */
    public $conversionArray = array('from' => 'ISO-8859-2', 'to' => 'UTF-8');

    /**
     * create date object with given timestamp, or current timestamp
     * 
     * @param mixed $date unix timestamp, or array(hour, minute, second, day, month, year)
     * @example __construct(23424242);
     * @example __construct(array(15, 0, 0, 24, 9, 2011));
     */
    public function __construct($date = FALSE)
    {
        if ($date && is_numeric($date)) {
            $this->_unixTimestamp = $date;
        } elseif (is_array($date)) {

            $dateArray   = array($date[4], $date[3], $date[5]);
            $valid       = Core_Time_Helper_Simple_Date::valid($dateArray);

            if (!$valid) {
                $this->err = 'INVALID_TIME_FORMAT';
                return;
            }

            $this->_unixTimestamp = mktime(
                $date[0],
                $date[1],
                $date[2],
                $date[4],
                $date[3],
                $date[5]
            );
        } else {
            $this->_unixTimestamp = time();
        }
    }

    /**
     * return formatted time stored in object
     * 
     * @return string
    */
    public function getFormattedTime()
    {
        return Core_Time_Helper_Simple_Date::getFormattedTime($this->_unixTimestamp);
    }

    /**
     * return date in dd-mm-yyyy or yyyy-mm-dd if $type is set on TRUE
     * 
     * @param boolean $type FALSE -> dd-mm-yyyy, TRUE -> yyyy-mm-dd
     * @return string
     * @example getDate(TRUE) - 2013-07-24
     * @example getDate() - 24-07-2013
     */
    public function getDate($type = FALSE)
    {
        return Core_Time_Helper_Simple_Date::getDate($this->_unixTimestamp, $type);
    }
    
    /**
     * return time in hh-mm-ss format
     * 
     * @return string
     */
    public function getTime()
    {
        return Core_Time_Helper_Simple_Date::getTime($this->_unixTimestamp);
    }

    /**
     * return full or short month name, correct with set up localization
     * 
     * @param boolean $short if TRUE return month name in short version
     * @return string
     */
    public function getMonthName($short = NULL)
    {
        $month = Core_Time_Helper_Simple_Date::getMonthName($this->_unixTimestamp, $short);

        if ($this->useConversion) {
            $month = Core_Time_Helper_Simple_Date::convert(
                $month,
                $this->conversionArray['from'],
                $this->conversionArray['to']
            );
        }

        return $month;
    }

    /**
     * return name of fill or short day in week, correct with set up localization
     * 
     * @param boolean $short if TRUE return day name in short version
     * @return string nazwa dnia w miesiacu 
     */
    public function getDayName($short = NULL)
    {
        $day = Core_Time_Helper_Simple_Date::getDayName($this->_unixTimestamp, $short);

        if ($this->useConversion) {
            $day = Core_Time_Helper_Simple_Date::convert(
                $day,
                $this->conversionArray['from'],
                $this->conversionArray['to']
            );
        }

        return $day;
    }

    /**
     * return current day or day name
     * 
     * @param boolean $name if TRUE return day name
     * @param boolean $short if TRUE return day name in short version
     * @return string|integer day name or number
     * @example getDay()
     * @example getDay(TRUE) - name
     * @example getDay(TRUE, TRUE) - name short version
     */
    public function getDay($name = FALSE, $short = FALSE)
    {
        $day = Core_Time_Helper_Simple_Date::getDay($this->_unixTimestamp, $name, $short);

        if($this->useConversion){
            $day = Core_Time_Helper_Simple_Date::convert(
                $day,
                $this->conversionArray['from'],
                $this->conversionArray['to']
            );
        }

        return $day;
    }

    /**
     * return day number in year
     * 
     * @return integer
     */
    public function getDayNumber()
    {
        return Core_Time_Helper_Simple_Date::getDayNumber($this->_unixTimestamp);
    }

    /**
     * return number of days in month
     * 
     * @return integer
     */
    public function getDayInMonth()
    {
        return Core_Time_Helper_Simple_Date::getDayInMonth($this->_unixTimestamp);
    }

    /**
     * return list of months in year, with month days number
     * 
     * @return array
     */
    public function getMonths()
    {
        return Core_Time_Helper_Simple_Date::getMonths($this->_unixTimestamp);
    }

    /**
     * check that year is leap-year
     * if is return TRUE
     * 
     * @return boolean
     */
    public function isLeapYear()
    {
        return Core_Time_Helper_Simple_Date::isLeapYear($this->_unixTimestamp);
    }

    /**
     * return current month number or its name
     * 
     * @param boolean $name if TRUE return month name
     * @param boolean $short if TRUE return month name in short version
     * @return string|integer month name or number
     * @example miesiac()
     * @example miesiac(1)
     * @example miesiac(1, 1)
     * @uses Core_Time_Helper_Simple_Date::miesiac()
     * @uses Core_Time_Helper_Date::$unix_timestamp
     * @uses Core_Time_Helper_Date::$conversion_array
     * @uses Core_Time_Helper_Date::$use_conversion
     */
    public function getMonth($name = FALSE, $short = FALSE)
    {
        $month = Core_Time_Helper_Simple_Date::getMonth($this->_unixTimestamp, $name, $short);
        
        if ($this->useConversion) {
            $month = Core_Time_Helper_Simple_Date::convert(
                $month,
                $this->conversionArray['from'],
                $this->conversionArray['to']
            );
        }
        
        return $month;
    }

    /**
     * return year
     * @return integer
     */
    public function getYear()
    {
        return Core_Time_Helper_Simple_Date::getYear($this->_unixTimestamp);
    }

    /**
     * return hour
     * 
     * @return integer
     */
    public function getHour()
    {
        return Core_Time_Helper_Simple_Date::getHour($this->_unixTimestamp);
    }

    /**
     * return minutes
     * 
     * @return integer
     */
    public function getMinutes()
    {
        return Core_Time_Helper_Simple_Date::getMinutes($this->_unixTimestamp);
    }

    /**
     * return seconds
     * 
     * @return integer
     */
    public function getSeconds()
    {
        return Core_Time_Helper_Simple_Date::getSeconds($this->_unixTimestamp);
    }

    /**
     * 
     * week number in year, correct with ISO8601:1998
     * @return integer
     */
    public function getWeek()
    {
        return Core_Time_Helper_Simple_Date::getWeek($this->_unixTimestamp);
    }

    /**
     * compare two dates, and check that rae the same
     * 
     * @param Core_Time_Helper_Date $data
     * @return boolean return TRUE if dates are the same
     */
    public function compareDates(Core_Time_Helper_Date $data)
    {
        if ($data === $this->_unixTimestamp) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * return list of differences between dates, or value or specific difference
     * if date in object is older, return as positive value, if younger, wil return negative
     * if some value is same, will return 0
     * default differences will return sec, min, hour, day, week, months, years
     * 
     * @param Core_Time_Helper_Date $data
     * @param string $differenceType type of differences (default all)
     * @param boolean $relative if FALSE will return absolute comparison, else depending of other parameters
     * @return mixed return difference, or array of differences
     * @example diff($data_object, 0, 1)
     * @example diff($data_object)
     * @example diff($data_object, 'days', 1)
     * @example diff($data_object, 'weeks', 0)
     * @example diff($data_object, 'hours')
     */
    public function getDifference(
        Core_Time_Helper_Date $data,
        $differenceType = NULL,
        $relative       = NULL
    ){
        switch ($differenceType) {
            case'seconds':
                return $this->_getSecondsDifference($data, $relative);
                break;

            case'years':
                return $this->_getYearsDifference($data);
                break;

            case'months':
                return $this->_getMonthsDifference($data, $relative);
                break;

            case'weeks':
                return $this->_getWeeksDifference($data, $relative);
                break;

            case'days':
                return $this->_getDaysDifference($data, $relative);
                break;

            case'hours':
                return $this->_getHoursDifference($data, $relative);
                break;

            case'minutes':
                return $this->_getMinutesDifference($data, $relative);
                break;

            default:
                $differenceList = array(
                    'seconds'  => $this->_getSecondsDifference($data, $relative),
                    'years'    => $this->_getYearsDifference($data),
                    'months'   => $this->_getMonthsDifference($data, $relative),
                    'weeks'    => $this->_getWeeksDifference($data, $relative),
                    'days'     => $this->_getDaysDifference($data, $relative),
                    'hours'    => $this->_getHoursDifference($data, $relative),
                    'minutes'  => $this->_getMinutesDifference($data, $relative)
                );

                return $differenceList;
                break;
        }
    }

    /**
     * return unix timestamp when try to get object as string
     * 
     * @return string
     */
    public function __toString()
    {
        return (string)$this->_unixTimestamp;
    }

    /**
     * return formatted time, correct with strftime() function
     * all depends from set localization
     * %a - dy short name
     * %A - full day name
     * %b - short month name
     * %B - full month name
     * %c - prefer date and time representation
     * %C - age number (year/100 and shorted to integer, interval from 00 to 99)
     * %d - day of month as integer number (from 01 to 31)
     * %D - same as co %m/%d/%y
     * %e - day of month as integer number, but put space before number (from" 1" to "31")
     * %g - as same as %G,but without age
     * %G - year in xxxx format, related with ISO week number
     * %h - as same as %b
     * %H - hour in 24-hour system (from 00 to 23)
     * %I - hour in 12-hour system (from 01 to 12)
     * %j - day of year as integer (from 001 to 366)
     * %m - month as integer (from 01 to 12)
     * %M - minutes as integer
     * %n - new line
     * %p - "am: or "pm" according to given time
     * %r - time in a.m. or p.m. notation
     * %R - time in 24-hour notation
     * %S - seconds as integer
     * %t - tab symbol
     * %T - current time, same as %H:%M:%S
     * %u - day of week number as integer (from 1 to 7) 1-monday
     * %U - week of year number as integer, start from first sunday as first day of first week
     * %V - week number of current year in ISO 8601:1988 as integer (from 01 to 53)
     *      first week is a week that have las 4 days
     * %W - week number of current year, start from first monday of first week
     * %w - day of week as integer, starts from sunday (0-sunday)
     * %x - prefer date presentation, without time
     * %X - prefer time representation, without date
     * %y - year as integer, without age (from 00 to 99)
     * %Y - year as integer in  xxxx format
     * %Z - time zone as name or shortcut
     * %% - "%" symbol
     * 
     * @param string $format
     * @return mixed
     * @example getOtherFormats("%A - day name correct with localization ")
     */
    public function getOtherFormats($format)
    {
        $formatted = strftime ($format, $this->_unixTimestamp);

        if($this->useConversion){
            $formatted = Core_Time_Helper_Simple_Date::convert(
                $formatted,
                $this->conversionArray['from'],
                $this->conversionArray['to']
            );
        }

        return $formatted;
    }

    /**
     * check that date is correct
     * 
     * @return boolean
     */
    public function checkDate()
    {
        return Core_Time_Helper_Simple_Date::valid($this->_unixTimestamp);
    }

    /**
     * return differences between seconds
     * 
     * @param Core_Time_Helper_Date $date
     * @param boolean $relative absolute difference, or depending of set time
     * @return integer difference
     */
    protected function _getSecondsDifference(Core_Time_Helper_Date $date, $relative)
    {
        if ($relative) {
            return $this->getSeconds() - $date->getSeconds();
        }

        $data = (int)$date->__toString();
        return $this->_unixTimestamp - $data;
    }

    /**
     * return differences between minutes
     * 
     * @param Core_Time_Helper_Date $date
     * @param boolean $relative absolute difference, or depending of set time
     * @return integer difference
     */
    protected function _getMinutesDifference(Core_Time_Helper_Date $date, $relative)
    {
        if ($relative) {
            return $this->getMinutes() - $date->getMinutes();
        }

        $diff = $this->_calculateTimeDifference($date);
        return floor($diff/60);
    }

    /**
     * return differences between hours
     *
     * @param Core_Time_Helper_Date $date
     * @param boolean $relative absolute difference, or depending of set time
     * @return integer difference
     */
    protected function _getHoursDifference(Core_Time_Helper_Date $date, $relative)
    {
        if ($relative) {
            return $this->getHour() - $date->getHour();
        }

        $diff = $this->_calculateTimeDifference($date);
        return floor($diff/60/60);
    }

    /**
     * return differences between weeks
     *
     * @param Core_Time_Helper_Date $date
     * @param boolean $relative absolute difference, or depending of set time
     * @return integer difference
     */
    protected function _getWeeksDifference(Core_Time_Helper_Date $date, $relative)
    {
        if ($relative) {
            return $this->getWeek() - $date->getWeek();
        }

        $diff = $this->_calculateTimeDifference($date);
        return floor($diff/7/24/60/60);
    }

    /**
     * return differences between days
     *
     * @param Core_Time_Helper_Date $date
     * @param boolean $relative absolute difference, or depending of set time
     * @return integer difference
     */
    protected function _getDaysDifference(Core_Time_Helper_Date $date, $relative)
    {
        if ($relative) {
            return $this->getDay() - $date->getDay();
        }

        $diff = $this->_calculateTimeDifference($date);
        return floor($diff/24/60/60);
    }

    /**
     * return differences between months
     *
     * @param Core_Time_Helper_Date $date
     * @param boolean $relative absolute difference, or depending of set time
     * @return integer difference
     */
    protected function _getMonthsDifference(Core_Time_Helper_Date $date, $relative)
    {
        if ($relative) {
            return $this->getMonth() - $date->getMonth();
        }

        $diff = $this->_calculateTimeDifference($date);
        return floor($diff/12/24/60/60);
    }

    /**
     * return differences between years
     *
     * @param Core_Time_Helper_Date $date
     * @return integer difference
     */
    protected function _getYearsDifference(Core_Time_Helper_Date $date)
    {
        return $this->getYear() - $date->getYear();
    }

    /**
     * return difference between seconds for given time
     * 
     * @param Core_Time_Helper_Date $data
     * @return integer difference
     */
    private function _calculateTimeDifference(Core_Time_Helper_Date $data)
    {
        $data = (int)$data->__toString();

        return $this->_unixTimestamp - $data;
    }
}
