<?php
/**
 * base class for all shell scripts
 *
 * @package     Core
 * @subpackage  Shell
 * @author      chajr <chajr@bluetree.pl>
 * @todo read some config from shell ini file (without usage main configuration file)
 */
abstract class Core_Shell_Model_Abstract
{
    /**
     * input arguments
     *
     * @var array
     */
    protected $_arguments = [];

    /**
     * regular expressions to detect arguments and values
     *
     * @var array
     */
    protected $_expressions = [
        '--param'   => '#^--([\w\d_-]{1,})$#',
        '-param'    => '#^-([\w\d_]{1,})$#',
        'value'     => '#^([\w\d_]{1,})$#'
    ];

    /**
     * value of special variable tu run script from browser
     *
     * @var string
     */
    protected $_forceScriptRun = 'true';

    /**
     * information that script was lunched from browser
     *
     * @var bool
     */
    protected $_isBrowser = FALSE;

    /**
     * information that script can be lunched from browser
     *
     * @var bool
     */
    protected $_allowBrowser = FALSE;

    /**
     * turn off colorize string
     *
     * @var bool
     */
    protected $_noColors = FALSE;

    /**
     * value for point that [OK] or [ERROR] string will show up
     * @var int
     */
    protected $_infoAlign = 30;

    /**
     * parse input parameters and start shell script
     * @todo read some data from configuration file
     */
    public function __construct()
    {
        $this->_beforeRun();
        $this->_checkAccess();
        $this->_browserPageStart();
        $this->_parseInput();
        $this->_showHelp();
        $this->run();
        $this->_browserPageEnd();
    }

    /**
     * prepare header for browser
     *
     * @return Core_Shell_Model_Abstract
     */
    protected function _browserPageStart()
    {
        if ($this->_isBrowser) {
            echo '<html><body style="background-color:#000;color:#fff;line-height:25px"><pre>';
        }

        return $this;
    }

    /**
     * prepare footer for browser page
     *
     * @return Core_Shell_Model_Abstract
     */
    protected function _browserPageEnd()
    {
        if ($this->_isBrowser) {
            echo '</pre></body></html>';
        }

        return $this;
    }

    /**
     * parse input arguments
     *
     * @return Core_Shell_Model_Abstract
     */
    protected function _parseInput()
    {
        if ($this->_isBrowser) {
            $this->_parseBrowserInput();
        } else {
            $this->_parseShellInput();
        }

        if ($this->getArgument('no-colors')) {
            $this->_noColors = TRUE;
        }

        return $this;
    }

    /**
     * parse arguments from shell command
     *
     * @return Core_Shell_Model_Abstract
     */
    protected function _parseShellInput()
    {
        $current = NULL;

        foreach ($_SERVER['argv'] as $arguments) {
            $doubleDash     = preg_match($this->_expressions['--param'], $arguments, $matchesDouble);
            $singleDash     = preg_match($this->_expressions['-param'], $arguments, $matchesSingle);
            $matches        = array_merge($matchesDouble, $matchesSingle);

            if ($doubleDash || $singleDash) {
                $current                        = $matches[0];
                $this->_arguments[$current]     = TRUE;
            } else {
                if ($current) {
                    $this->_arguments[$current] = $arguments;
                } else if (preg_match($this->_expressions['value'], $arguments, $matches)) {
                    $this->_arguments[$matches[1]] = TRUE;
                }
            }
        }

        return $this;
    }

    /**
     * parse arguments from browser
     *
     * @return Core_Shell_Model_Abstract
     */
    protected function _parseBrowserInput()
    {
        $list = $_GET;
        unset($list['run']);
        $this->_arguments = $list;

        return $this;
    }

    /**
     * check that script can be lunched
     *
     * @return Core_Shell_Model_Abstract
     */
    protected function _checkAccess()
    {
        $isGet = isset($_GET['run']) && $_GET['run'] === $this->_forceScriptRun;

        if ($isGet && $this->_allowBrowser) {
            $this->_isBrowser = TRUE;
            return $this;
        }

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $this->_isBrowser   = TRUE;
            $message            = 'Access to shell script from browser: ' . $_SERVER['REQUEST_URI'];
            $string             = $this->_colorizeString('Access deny.', 'white');

            $this->_browserPageStart();
            echo $this->_colorizeString($string, 'red_label');
            $this->_browserPageEnd();
            Loader::log('access', $message, 'shell');
            exit;
        }

        return $this;
    }

    /**
     * check that help must be displayed
     * after display close script
     */
    protected function _showHelp()
    {
        $helpShort  = isset($this->_arguments['-h']);
        $helpLong   = isset($this->_arguments['--help']);

        if ($helpShort || $helpLong) {
            echo $this->usageHelp();
            exit;
        }
    }

    /**
     * get argument value
     *
     * @param string $name
     * @return mixed
     */
    public function getArgument($name)
    {
        if (isset($this->_arguments[$name])) {
            return $this->_arguments[$name];
        }

        return FALSE;
    }

    /**
     * check that given argument exist
     *
     * @param string $name
     * @return bool
     */
    public function hasArgument($name)
    {
        if (isset($this->_arguments[$name])) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * add some colors to string
     *
     * @param string $string
     * @param string $type
     * @return string
     */
    protected function _colorizeString($string, $type)
    {
        if ($this->_noColors) {
            $type = NULL;
        }

        if ($this->_isBrowser) {
            $colors = $this->_colorizeBrowser($type);
        } else {
            $colors = $this->_colorizeShell($type);
        }

        return $colors['start'] . $string . $colors['end'];
    }

    /**
     * apply colors for shell
     *
     * @param string $type
     * @return array
     */
    protected function _colorizeShell($type)
    {
        $list = [
            'start' => '',
            'end'   => "\033[0m"
        ];

        switch ($type) {
            case 'red':
                $list['start'] = "\033[0;31m";
                break;

            case 'green':
                $list['start'] = "\033[0;32m";
                break;

            case 'brown':
                $list['start'] = "\033[0;33m";
                break;

            case 'black':
                $list['start'] = "\033[0;30m";
                break;

            case 'blue':
                $list['start'] = "\033[0;34m";
                break;

            case 'magenta':
                $list['start'] = "\033[0;35m";
                break;

            case 'cyan':
                $list['start'] = "\033[0;36m";
                break;

            case 'white':
                $list['start'] = "\033[0;37m";
                break;

            case 'red_label':
                $list['start'] = "\033[41m";
                break;

            case 'brown_label':
                $list['start'] = "\033[43m";
                break;

            case 'black_label':
                $list['start'] = "\033[40m";
                break;

            case 'green_label':
                $list['start'] = "\033[42m";
                break;

            case 'blue_label':
                $list['start'] = "\033[44m";
                break;

            case 'magenta_label':
                $list['start'] = "\033[45m";
                break;

            case 'cyan_label':
                $list['start'] = "\033[46m";
                break;

            case 'white_label':
                $list['start'] = "\033[47m";
                break;

            default:
                $list['end'] = '';
                break;
        }

        return $list;
    }

    /**
     * apply colors for browser
     *
     * @param string $type
     * @return array
     */
    protected function _colorizeBrowser($type)
    {
        $list = [
            'start' => '',
            'end'   => '</span>'
        ];

        switch ($type) {
            case 'red':
                $list['start'] = '<span style="color:red">';
                break;

            case 'green':
                $list['start'] = '<span style="color:green">';
                break;

            case 'brown':
                $list['start'] = '<span style="color:#a5471d">';
                break;

            case 'black':
                $list['start'] = '<span style="color:black">';
                break;

            case 'blue':
                $list['start'] = '<span style="color:#4c74ff">';
                break;

            case 'magenta':
                $list['start'] = '<span style="color:magenta">';
                break;

            case 'cyan':
                $list['start'] = '<span style="color:cyan">';
                break;

            case 'white':
                $list['start'] = '<span style="color:white">';
                break;

            case 'red_label':
                $list['start'] = '<span style="padding:5px;background-color:red">';
                break;

            case 'brown_label':
                $list['start'] = '<span style="padding:5px;background-color:#a5471d">';
                break;

            case 'black_label':
                $list['start'] = '<span style="padding:5px;background-color:black">';
                break;

            case 'green_label':
                $list['start'] = '<span style="padding:5px;background-color:green">';
                break;

            case 'blue_label':
                $list['start'] = '<span style="padding:5px;background-color:#4c74ff">';
                break;

            case 'magenta_label':
                $list['start'] = '<span style="padding:5px;background-color:magenta">';
                break;

            case 'cyan_label':
                $list['start'] = '<span style="padding:5px;background-color:cyan">';
                break;

            case 'white_label':
                $list['start'] = '<span style="padding:5px;background-color:white">';
                break;

            default:
                $list['end'] = '';
                break;
        }

        return $list;
    }

    /**
     * show formatted message about started method/action
     *
     * @param string $message
     * @param string $key
     * @return string
     */
    protected function formatOutput($message, $key)
    {
        if ($this->_isBrowser) {
            $length = strlen(strip_tags($key));
        } else {
            $length = strlen($key);
        }

        $spaces = $this->_infoAlign - $length;

        for ($i = 1;$i <= $spaces; $i++) {
            $key .= ' ';
        }

        return $key . ' ' . $message;
    }

    /**
     * Retrieve Usage Help Message
     */
    public function usageHelp()
    {
        return <<<USAGE

------------------------------

Usage:  php -f script.php -- [options]

    -h            Short alias for help
    --help        This help

------------------------------

USAGE;
    }

    /**
     * start before all actions
     */
    protected function _beforeRun(){}

    /**
     * Run script
     *
     */
    abstract public function run();
}
