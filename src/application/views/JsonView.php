<?php

/**
 * Creates a JSON render of the API response
 *
 * @package API
 * @author Andrew Patterson <patto@live.com.au>
 */
class JsonView extends BaseView {
    
    public function __construct($config = array()) {
        $this->_templateFileSuffix = 'json';
        $this->setContentType('application/json; charset=utf8');
        parent::__construct($config);
    }
    
    protected function formatArray($content) {
        $json = json_encode($content);
        return $json;
    }
}
