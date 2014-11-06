<?php

namespace Tranquility;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use AltoRouter                                 as Router;
use Symfony\Component\HttpFoundation\Request   as Request;
use Tranquility\Enum\System\HttpStatusCode     as EnumHttpStatusCode;

class Application {
    protected $_db = null;
    protected $_log = null;
    protected $_config = array();
    protected $_request;
    protected $_oauth = null;

    /**
     * Autoloader function
     *
     * @param string $classname Name of the class to load (should not be a filename)
     * @return boolean True upon successful autoload
     */
    public static function autoload($classname) {
        if (strpos($classname, '.') !== false) {
            // Supplied class was a filename
            exit();
        }

        // Provide mappings for Controllers, Mappers, Models, Views
        // and Services
        if (preg_match('/[a-zA-Z]+Controller$/', $classname)) {
            include(__DIR__ . '/../application/controllers/' . $classname . '.php');
            return true;
        } elseif (preg_match('/[a-zA-Z]+Mapper$/', $classname)) {
            include(__DIR__ . '/../application/models/' . $classname . '.php');
            return true;
        } elseif (preg_match('/[a-zA-Z]+Model$/', $classname)) {
            include(__DIR__ . '/../application/models/' . $classname . '.php');
            return true;
        } elseif (preg_match('/[a-zA-Z]+View$/', $classname)) {
            include(__DIR__ . '/../application/views/' . $classname . '.php');
            return true;
        } elseif (preg_match('/[a-zA-Z]+Service$/', $classname)) {
            include(__DIR__ . '/../application/services/' . $classname . '.php');
            return true;
        }
    }

    /**
     * Register application as the autoloader (also includes Composer autoload)
     */
    public static function registerAutoloader() {
        require(__DIR__.'/../../vendor/autoload.php');
        spl_autoload_register("Tranquility\\Application::autoload");
    }

    /**
     * Convert errors into ErrorException objects
     *
     * This method catches PHP errors and converts them into \ErrorException objects;
     * these \ErrorException objects are then thrown and caught by the custom
     * exception handler
     *
     * @param  int            $errno   The numeric type of the Error
     * @param  string         $errstr  The error message
     * @param  string         $errfile The absolute path to the affected file
     * @param  int            $errline The line number of the error in the affected file
     * @return bool
     * @throws \ErrorException
     */
    public static function handleErrors($errno, $errstr = '', $errfile = '', $errline = '') {
        if (!($errno & error_reporting())) {
            return;
        }

        throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
    }

    /**
     * Custom exception handler
     * 
     * @param \Exception $ex
     */
    public function handleExceptions(\Exception $ex) {
        $this->_log->critical($ex);
        
        // Instantiate Error Controller and invoke action
        if ($this->_request == null) {
            $this->_request = Request::createFromGlobals();
        }
        $controller = new \ErrorController($this->_request, $this->_config, $this->_db, $this->_log, $this->_oauth);
        $output = $controller->displayException($ex);
        echo $output;
    }

    /**
     * Constructor
     *
     * @param array $config Configuration parameters
     */
    public function __construct(array $config = array()) {
        // Load application configuration
        $this->config($config);

        // Set timezone for the application
        date_default_timezone_set($this->config('timezone'));

        // Set up logging
        $logConfig = $this->config('logging');
        $logLevel = constant("Monolog\\Logger::".strtoupper($logConfig['level']));
        $appLogStream = new StreamHandler($logConfig['path'].'/'.$logConfig['filename'], $logLevel);
        $this->_log = new Logger('APPLICATION');
        $this->_log->pushHandler($appLogStream);
        $this->_log->addDebug('Logging successfully initialised');

        // If database config has been supplied, create connection
        $dbConfig = $this->config('database');
        $dbLogStream = new StreamHandler($logConfig['path'].'/'.$dbConfig['logfile'], $logLevel);
        $dbLogger = new Logger("DATABASE");
        $dbLogger->pushHandler($dbLogStream);
        if ($dbConfig !== null) {
            switch($dbConfig['driver']) {
                case 'mysql':
                case 'mysqli':
                    $this->_db = new Database($dbLogger,
                                              'mysql:host='.$dbConfig['hostname'].';dbname='.$dbConfig['username'],
                                              $dbConfig['username'],
                                              $dbConfig['password']);
                    break;
                default:
                    throw new Exception('Unknown / unsupported database driver provided: '.$dbConfig['driver']);
            }
        }

        // Set charset for the connection
        $params = array(':charset' => $dbConfig['charset'], ':collation' => $dbConfig['collation']);
        $stmt = $this->_db->prepare("SET NAMES :charset COLLATE :collation");
        $stmt->execute($params);

        $params = array(':charset' => $dbConfig['charset']);
        $stmt = $this->_db->prepare("SET CHARACTER SET :charset");
        $stmt->execute($params);
    }

    /**
     * Main execution pathway for application:
     *   - Sets up error handling
     *   - OAuth authentication
     *   - Routing
     *   - Route dispatch and execution
     *   - Rendering
     * 
     * @throws Exception
     */
    public function run() {
        // Set up error handling
        set_error_handler(array('Tranquility\\Application', 'handleErrors'));
        set_exception_handler(array($this, 'handleExceptions'));
        
        // Set up OAuth2 server
        $this->_oauth = $this->_initialiseOAuth2Server();

        // Load routing details
        $router = new Router();
        include(__DIR__.'/../application/routes.php');
        $routeDetails = $router->match();

        // Check that the route has been matched
        if (!isset($routeDetails['target'])) {
            // Throw an exception with a 404 error code
            throw new Exception('No matching route for this request.', EnumHttpStatusCode::NotFound);
        }
        
        // Collect URL and headers, and add parameters from route
        $this->_request = Request::createFromGlobals();
        $this->_request->attributes->add($routeDetails['params']);
        
        // Determine controller and action, and execute
        $classname = Utility::extractValue($routeDetails['target'], 'controller', 'DefaultController');
        $action = Utility::extractValue($routeDetails['target'], 'action', 'indexAction');

        // Instantiate controller and invoke action
        $controller = new $classname($this->_request, $this->_config, $this->_db, $this->_log, $this->_oauth);
        $output = call_user_func_array(array($controller, $action), $routeDetails['params']);
        echo $output;

        // End of run - restore normal error handlers
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * Getter and setter of application settings
     *
     * If only one argument is specified and that argument is a string, the value
     * of the setting identified by the first argument will be returned, or NULL if
     * that setting does not exist.
     *
     * If only one argument is specified and that argument is an associative array,
     * the array will be merged into the existing application settings.
     *
     * If two arguments are provided, the first argument is the name of the setting
     * to be created or updated, and the second argument is the setting value.
     *
     * @param  string|array $name  If a string, the name of the setting to set or retrieve. Else an associated array of setting names and values
     * @param  mixed        $value If name is a string, the value of the setting identified by $name
     * @return mixed        The value of a setting if only one argument is a string
     */
    public function config($name, $value = null) {
        // Check how the first parameter is provided
        if (is_array($name)) {
            // Array of config values provided - merge into existing array
            if (true === $value) {
                $this->_config = array_merge_recursive($this->_config, $name);
            } else {
                $this->_config = array_merge($this->_config, $name);
            }
        } elseif (func_num_args() === 1) {
            // Only name has been provided - return value, or NULL if the value
            // does not exist
            if (isset($this->_config[$name])) {
                return $this->_config[$name];
            } else {
                return null;
            }
        } else {
            // Set value for specified config item
            $this->_config[$name] = $value;
        }
    }
    
    private function _initialiseOAuth2Server() {
        $storage = new \Tranquility\OAuth2\Storage\Pdo($this->_db);
        $server = new \OAuth2\Server($storage);
        
        // Add grant types
        $server->addGrantType(new \OAuth2\GrantType\ClientCredentials($storage));
        $server->addGrantType(new \OAuth2\GrantType\AuthorizationCode($storage));
        return $server;
    }
}