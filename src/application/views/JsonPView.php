<?php

/**
 * Creates a JSON render of the API response enclosed in a callback function
 *
 * @package API
 * @author Andrew Patterson <patto@live.com.au>
 */
class JsonPView extends JsonView {
    
    private $_callback = '';
    
    public function __construct($callback) {
        $this->_callback = $callback;
    }
    
    public function render($content, $statusCode) {
        // Set headers
        $json = $this->printArray($content);
        $json = $this->_callback."(".$json.");";
        
        $response = Response::make($json, $statusCode);
        $response->header('Content-Type', 'application/json; charset=utf8');
        return $response;
    }
}
