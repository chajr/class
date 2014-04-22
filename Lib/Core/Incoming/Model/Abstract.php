<?php
/**
 * class for all global variable class
 * process all data from GET, POST, FILES and convert them to blue objects
 *
 * @package     Core
 * @subpackage  Incoming
 * @author      chajr <chajr@bluetree.pl>
 */
class Core_Incoming_Model_Abstract extends Core_Blue_Model_Object
{
    /**
     * depends of type, convert global array to blue object with validation
     * 
     * @param string $type
     */
    public function __construct($type)
    {
        Loader::tracer('process incoming data: ' . $type, debug_backtrace(), '004396');

        switch ($type) {
            case 'get':
                $array = $_GET;
                $this->_maxParameters(count($array), 'get');
                break;

            case 'post':
                $array = $_POST;
                $this->_maxParameters(count($array), 'post');
                break;

            case 'file':
                $array = $_FILES;
                $this->_maxParameters(count($array), 'files');
                break;

            default:
                return;
        }

        parent::__construct($array);
    }

    /**
     * destroy all super global arrays
     */
    public static function destroy()
    {
        Loader::tracer('destroy global arrays', debug_backtrace(), '004396');

        unset($_GET);
        unset($_POST);

        $_COOKIE    = [];
        $_SESSION   = [];

        unset($_FILES);
        unset($_REQUEST);
    }

    /**
     * set data given in constructor with checking keys
     * if key is incorrect, create log file
     *
     * @param mixed $data
     * @return Core_Blue_Model_Object
     */
    protected function _appendArray($data)
    {
        foreach ((array)$data as $key => $val) {
            try {
                $this->_checkKey($key);
                $key                = $this->_validateByKeyName($key, $val);
                $this->_DATA[$key]  = $val;
            } catch (Exception $e) {
                Loader::log('invalid_key', $key . '=>' . $val, 'invalid key');
            }
        }

        if ($this->hasErrors()) {
            Loader::log('security', $this->_errorsList, 'non validate data');
        }

        return $this;
    }

    /**
     * check max number of parameters
     *
     * @param integer $counter number of given parameter
     * @param string $type get|post|files
     * @throws Exception
     */
    protected function _maxParameters($counter, $type)
    {
        Loader::tracer('check max parameters count', debug_backtrace(), '004396');

        $globalArray    = '';
        $option         = '';

        switch ($type) {
            case"get":
                $option         = 'parameter_count_get';
                $globalArray    = $_GET;
                break;

            case"post":
                $option         = 'parameter_count_post';
                $globalArray    = $_POST;
                break;

            case"files":
                $option         = 'files_count';
                $globalArray    = $_FILES;
                break;
        }

        $option = Loader::getConfiguration()->getSecure()->getData($option);
        if ($option) {
            if ($counter > $option) {
                $inf = 'To many parameters: ' . count($globalArray) . ' -> ' . $option;
                throw new Exception($inf);
            }
        }
    }

    /**
     * check that name for variable has proper chars
     *
     * @param string $key
     * @throws Exception
     */
    protected function _checkKey($key)
    {
        $expression = Loader::getConfiguration()->getSecure()->getParameterExpression();
        $keyCheck   = preg_match($expression, $key);

        if (!$keyCheck) {
            throw new Exception(
                'Invalid parameter key: ' . $key . ' - rewrite: ' . $expression
            );
        }
    }

    /**
     * check that input value is correct with given in input name pattern
     * 
     * @param string $key
     * @param mixed $value
     * @return string
     */
    protected function _validateByKeyName($key, $value)
    {
        /** @var Core_Blue_Model_Object $config */
        $config     = Loader::getConfiguration()->getSecure();
        $matches    = [];

        if ($config->getUseInputPatterns()) {
            preg_match_all($config->getInputPattern(), $key, $matches);

            foreach ($matches[0] as $validator) {
                $validatorKey   = str_replace('--', '', $validator);
                $valid          = Core_Blue_Helper_Validator::valid($value, $validatorKey);

                if (!$valid) {
                    $this->_hasErrors           = TRUE;
                    $this->_errorsList[$key]    = [
                        'validator' => $validatorKey,
                        'value'     => $value,
                        'pattern'   => Core_Blue_Helper_Validator::$regularExpressions[$validatorKey]
                    ];
                }
            }

            if ($config->getClearInputName()) {
                preg_match($config->getClearInputPattern(), $key, $match);
                $key = str_replace($match, '', $key);
            }
        }

        return $key;
    }
}
