<?php

define('TRANQULITY_START', microtime(true));

// Setup autoloader
include('../library/application.php');
include('../config/application.php');

// Define application environment
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Create the application instance, and process the request
\Tranquility\Application::registerAutoloader();
$application = new \Tranquility\Application($config[APPLICATION_ENV]);
$application->run();