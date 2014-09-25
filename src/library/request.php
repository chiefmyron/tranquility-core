<?php

namespace Tranquility;

class Request {
	
	protected $_oauthModel;
	protected $_config;
	protected $_log;

	// Request parts
	protected $_verb;
	protected $_host;
	protected $_scheme;
	protected $_path;
	protected $_urlElements;
	protected $_baseUri;
	protected $_contentType;
	protected $_accepts = array();
	protected $_parameters = array();

	public function __construct($log, $config = array()) {
		// Set up logger
		$this->_log = $log;

		// Set server variables
		$this->setVerb(Utility::extractValue($_SERVER, 'REQUEST_METHOD', null));
		$this->setPath(Utility::extractValue($_SERVER, 'PATH_INFO', null));
		$this->setHost(Utility::extractValue($_SERVER, 'HTTP_HOST', null));
		$this->setAccept(Utility::extractValue($_SERVER, 'HTTP_ACCEPT', null));
		$this->setContentType(Utility::extractValue($_SERVER, 'CONTENT_TYPE', null));

		// Set scheme based on HTTPS details in request
		$https = Utility::extractValue($_SERVER, 'HTTPS', 'off');
		if ($https == 'on') {
			$this->setScheme('https://');
		} else {
			$this->setScheme('http://');
		}

		// Set the base URI for the request
		$this->setBase($this->getScheme().$this->getHost());

		// Parse parameters of the request
		$this->parseParameters();
		$this->_log->debug('New request received [Verb: '.$this->getVerb().', Base URL: '.$this->getBase().', Path: '.$this->getPath());
	}

	/**
	 * Retrieves the HTTP verb of the request
	 * 
	 * @return string
	 */
	public function getVerb() {
		return $this->_verb;
	}

	/**
	 * Manually sets the HTTP verb for the request
	 *
	 * @param string $verb The HTTP verb to set
	 * @return \Tranquility\Request
	 */
	public function setVerb($verb) {
		$this->_verb = $verb;
		return $this;
	}

	/**
	 * Retrieves the hostname of the request
	 * 
	 * @return string
	 */
	public function getHost() {
		return $this->_host;
	}

	/**
	 * Manually sets the hostname for the request
	 *
	 * @param string $host The hostname to set
	 * @return \Tranquility\Request
	 */
	public function setHost($host) {
		$this->_host = $host;
		return $this;
	}

	/**
	 * Retrieves the HTTP scheme of the request
	 * 
	 * @return string
	 */
	public function getScheme() {
		return $this->_scheme;
	}

	/**
	 * Manually sets the HTTP scheme for the request
	 *
	 * @param string $scheme The HTTP scheme to set
	 * @return \Tranquility\Request
	 */
	public function setScheme($scheme) {
		$this->_scheme = $scheme;
		return $this;
	}

	/**
	 * Retrieves the path information from the original request
	 * 
	 * @return string
	 */
	public function getPath() {
		return $this->_path;
	}

	/**
	 * Manually sets the path for the request
	 *
	 * @param string $path The path in the URI
	 * @return \Tranquility\Request
	 */
	public function setPath($path) {
		$this->_path = $path;
		$this->_urlElements = explode('/', substr($path, 1));  // Strip leading backslash to prevent empty segment
		return $this;
	}

	/**
	 * Retrieves the base URL for the request
	 * 
	 * @return string
	 */
	public function getBase() {
		return $this->_baseUri;
	}

	/**
	 * Manually sets the base URL for the request
	 *
	 * @param string $base The base URL
	 * @return \Tranquility\Request
	 */
	public function setBase($base) {
		$this->_baseUri = $base;
		return $this;
	}

	/**
	 * Retrieves the content type of the request
	 * 
	 * @return string
	 */
	public function getContentType() {
		return $this->_contentType;
	}

	/**
	 * Manually sets the content type for the request
	 *
	 * @param string $contentType The content type
	 * @return \Tranquility\Request
	 */
	public function setContentType($contentType) {
		$this->_contentType = $contentType;
		return $this;
	}

	/**
	 * Returns a URL element by index (with 0 being the first element). If the
	 * element does not exist, the default value will be returned
	 *
	 * @param int $index Index of the element to retrieve
	 * @param string $default
	 * @return string
	 */
	public function getUrlElement($index, $default = '') {
            $index = (int)$index;
            $element = $default;

            if (isset($this->_urlElements[$index])) {
                    $element = $this->_urlElements[$index];
            }

            return $element;
	}

	/**
	 * Manually sets the MIME types that will be accepted for the request
	 *
	 * @param string $accept The HTTP header string with the accept MIME types
	 * @return \Tranquility\Request
	 */
	public function setAccept($accept) {
            $this->_accepts = explode(',', $accept);
            return $this;
	}

	/**
	 * Determines if the headers indicate that a particular MIME type is accepted
	 * based on the browser headers
 	 *
 	 * @param string $header MIME type to check for
 	 * @return boolean
 	 */
	public function accepts($header) {
            foreach ($this->_accepts as $accept) {
                    if (strstr($accept, $header) !== false) {
                            return true;
                    }
            }

            return false;
	}
        
        public function getPreferredContentType() {
            if (is_array($this->_accepts)) {
                return $this->_accepts[0];
            }
            
            // Default to JSON
            return 'json';
        }

	public function parseParameters() {
            // Retrieve GET parameters from query string
            if (isset($_SERVER['QUERY_STRING'])) {
                parse_str($_SERVER['QUERY_STRING'], $parameters);
                $this->_parameters = $parameters;
            }

            // For POST or PUT requests, any variables in the request
            // body will overwrite those in the query string
            if ($this->getVerb() == 'POST' || $this->getVerb() == 'PUT') {
                $body = file_get_contents('php://input');

                switch($this->getContentType()) {
                    case 'application/xml':
                        throw new Exception('XML is not a supported request type at this time');
                        break;
                    case 'application/json':
                    default:
                        $bodyParams = json_decode($body);
                        foreach($bodyParams as $paramName => $paramValue) {
                                $this->_parameters[$paramName] = $paramValue;
                        }
                        break;
                }
            }

            return true;
	}

	public function getParameter($name, $defaultValue = null) {
            return Utility::extractValue($this->_parameters, $name, $defaultValue);
	}

        public function getParameters() {
            return $this->_parameters;
        }
        
        public function addParameter($param, $value = null) {
            if (is_array($param)) {
                // Merge new parameter array with existing parameters
                $this->_parameters = array_merge($this->_parameters, $param);
            } else {
                $this->_parameters[$param] = $value;
            }
        }
}