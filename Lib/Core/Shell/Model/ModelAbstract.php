<?php
/**
 * base class for all shell scripts
 *
 * @package     Core
 * @subpackage  Shell
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Shell\Model;
use Loader;
use Core\Blue\Model\Configuration;
abstract class ModelAbstract
{
    const HEADER = <<<EOT
 ____    ___                    __                          
/\  _`\ /\_ \                  /\ \__                       
\ \ \L\ \//\ \    __  __     __\ \ ,_\  _ __    __     __   
 \ \  _ <'\ \ \  /\ \/\ \  /'__`\ \ \/ /\`'__\/'__`\ /'__`\ 
  \ \ \L\ \\\_\ \_\ \ \_\ \/\  __/\ \ \_\ \ \//\  __//\  __/ 
   \ \____//\____\\\ \____/\ \____\\\ \__\\\ \_\\\ \____\ \____\
    \/___/ \/____/ \/___/  \/____/ \/__/ \/_/ \/____/\/____/
 __           __        
/\ \       __/\ \       
\ \ \     /\_\ \ \____  
 \ \ \  __\/\ \ \ '__`\ 
  \ \ \L\ \\\ \ \ \ \L\ \
   \ \____/ \ \_\ \_,__/
    \/___/   \/_/\/___/ 
     _          _ _                 _       _   
 ___| |__   ___| | |  ___  ___ _ __(_)_ __ | |_ 
/ __| '_ \ / _ \ | | / __|/ __| '__| | '_ \| __|
\__ \ | | |  __/ | | \__ \ (__| |  | | |_) | |_ 
|___/_| |_|\___|_|_| |___/\___|_|  |_| .__/ \__|
                                     |_|        
EOT;

    const ALL_CONFIGURATION = 'all';

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
     * information that script was lunched from browser
     *
     * @var bool
     */
    protected $_isBrowser = FALSE;

    /**
     * store modules configuration
     * 
     * @var array
     */
    protected $_configurationCache = [];

    /**
     * parse input parameters and start shell script
     */
    public function __construct()
    {
        $this->_beforeRun();
        $this->_checkAccess();
        $this->_browserPageStart();

        $displayHeader = $this->_getShellConfiguration('display_header');
        if ($displayHeader === 'enabled') {
            echo self::HEADER;
        }

        $this->_parseInput();
        $this->_showHelp();
        $this->run();
        $this->_browserPageEnd();
    }

    /**
     * get configuration for specified module
     * or merged configuration for all modules
     * 
     * @param string $configuration
     * @param string $module
     * @return null|mixed
     * 
     * @example _getConfiguration('some_config', 'Some\Module');
     * @example _getConfiguration('some_config', 'all');
     */
    protected function _readConfiguration($configuration, $module = self::ALL_CONFIGURATION)
    {
        $cachedModule  = isset($this->_configurationCache[$module]);
        $config        = isset($this->_configurationCache[$module][$configuration]);
        if ($cachedModule && $config) {
            return $this->_configurationCache[$module][$configuration];
        }

        if ($module === self::ALL_CONFIGURATION) {
            return $this->_getCompleteConfiguration($configuration);
        } else {
            return $this->_getModuleConfiguration($configuration, $module);
        }
    }

    /**
     * get all merged configuration using Core\Blue\Model\Configuration
     * 
     * @param string $configuration
     * @return null|mixed
     */
    protected function _getCompleteConfiguration($configuration)
    {
        $config                                             = new Configuration();
        $cachedConfiguration                                = $config->getData();
        $this->_configurationCache[self::ALL_CONFIGURATION] = $cachedConfiguration;

        if (isset($cachedConfiguration[$configuration])) {
            return $cachedConfiguration[$configuration];
        }

        return NULL;
    }

    /**
     * get configuration for specified module
     * 
     * @param string $configuration
     * @param string $module
     * @return null|mixed
     */
    protected function _getModuleConfiguration($configuration, $module)
    {
        $basePath   = CORE_LIB . Loader::name2path($module, FALSE) . '/etc/config.';
        $ini        = $basePath . 'ini';
        $json       = $basePath . 'json';
        $php        = $basePath . 'php';

        switch (TRUE) {
            case file_exists($ini):
                $data = parse_ini_file($ini, TRUE);
                break;

            case file_exists($json):
                $data = file_get_contents($json);
                $data = json_decode($data, TRUE);
                break;

            case file_exists($php):
                $data =  file_get_contents($php, TRUE);
                break;

            default:
                return NULL;
                break;
        }

        $this->_configurationCache[$module] = $data;

        if (isset($data[$configuration])) {
            return $data[$configuration];
        }

        return NULL;
    }

    /**
     * read configuration for shell script
     * 
     * @param string $config
     * @return null|mixed
     */
    protected function _getShellConfiguration($config)
    {
        $data = $this->_readConfiguration('core_shell', 'Core\Shell');

        if (isset($data[$config])) {
            return $data[$config];
        }

        return NULL;
    }

    /**
     * set configuration for shell script
     * 
     * @param string $key
     * @param $value mixed
     * @return ModelAbstract
     */
    protected function _setShellConfiguration($key, $value)
    {
        $this->_getShellConfiguration('');
        $this->_configurationCache['Core\Shell']['core_shell'][$key] = $value;

        return $this;
    }

    /**
     * prepare header for browser
     *
     * @return ModelAbstract
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
     * @return ModelAbstract
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
     * @return ModelAbstract
     */
    protected function _parseInput()
    {
        if ($this->_isBrowser) {
            $this->_parseBrowserInput();
        } else {
            $this->_parseShellInput();
        }

        if ($this->getArgument('no-colors')) {
            $this->_configurationCache['Core\Shell']['no_colors'] = 'enabled';
        }

        return $this;
    }

    /**
     * parse arguments from shell command
     *
     * @return ModelAbstract
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
     * @return ModelAbstract
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
     * @return ModelAbstract
     */
    protected function _checkAccess()
    {
        $forceScript = $this->_getShellConfiguration('force_script_run');
        $isGet = isset($_GET['run']) && $_GET['run'] === $forceScript;

        $allowBrowser = $this->_getShellConfiguration('allow_browser');
        if ($isGet && $allowBrowser === 'enabled') {
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
        $colors = $this->_getShellConfiguration('no_colors');
        if ($colors === 'enabled') {
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

        $align  = $this->_getShellConfiguration('info_align');
        $spaces = $align - $length;

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
