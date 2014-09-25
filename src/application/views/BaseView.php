<?php

use Tranquility\Utility                    as Utility;
use Tranquility\Enum\System\HttpStatusCode as EnumHttpStatusCode;

/**
 * Description of BaseView
 *
 * @author Andrew
 */
abstract class BaseView {
    
    /**
     * Content-Type header value
     * @var string
     */
    protected $_contentType = '';
    
    /**
     * HTTP status code
     * @var int
     */
    protected $_statusCode = 0;
    
    /**
     * Name of template to use
     * @var string
     */
    protected $_templateName;
    
    /**
     * Base template folder location
     * @var string
     */
    protected $_templatePath;
    
    /**
     * Template file to use
     * @var string
     */
    protected $_filename;
    
    /**
     * Needs to be set by the output specific view
     * @param type $config
     */
    protected $_templateFileSuffix;
    
    public function __construct($config = array()) {
        $this->_templateName = Utility::extractValue($config, 'name', 'default');
        $this->_templatePath = Utility::extractValue($config, 'path', null);
        $this->_filename     = Utility::extractValue($config, 'defaultTemplate', 'index');
    }
    
    public function render() {
        // Set headers and response code
        header('Content-Type: '.$this->_contentType);
        http_response_code($this->_statusCode);
        
        ob_start();
        include($this->_templatePath.$this->_templateName.'/'.$this->_filename.'.'.$this->_templateFileSuffix.'.php');
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }
    
    public function setContentType($contentType) {
        $this->_contentType = $contentType;
    }
    
    public function getContentType() {
        return $this->_contentType;
    }
    
    public function setHttpStatusCode($code) {
        if (!EnumHttpStatusCode::isValidValue($code)) {
            throw new Exception('Unsupported HTTP status code supplied: '.$code);
        }
        $this->_statusCode = $code;
    }
    
    public function getHttpStatusCode($code) {
        return $this->_statusCode;
    }
    
    public function setFilename($filename) {
        $this->_filename = $filename;
    }
    
    public function getFilename($filename) {
        return $this->_filename;
    }
    
    protected function addCount($content) {
        if (!is_array($content)) {
            return $content;
        }
        
        foreach ($content as $name => $item) {
            if ($name != "meta") {
                $content['meta']['count'] = count($item);
            }
        }
        
        return $content;
    }
}
