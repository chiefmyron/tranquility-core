<?php

use Carbon\Carbon                             as Carbon;
use Tranquility\Utility                       as Utility;
use Tranquility\Enum\System\TransactionSource as EnumTransactionSource;

class BaseController {
    protected $_db;
    protected $_log;
    protected $_view;
    protected $_config;
    protected $_request;
    protected $_oauth;

    // Array of data mappers
    protected $_mappers = array();

    // Pagination members
    protected $_start = 0;
    protected $_resultsPerPage = 20;
    protected $_verbose = false;

    public function __construct($request, $config, $db, $log, $oauth) {
        $this->_db = $db;
        $this->_log = $log;
        $this->_config = $config;
        $this->_request = $request;
        $this->_oauth = $oauth;

        // Set pagination and verbosity details
        $this->_start          = $this->_request->query->get('start', 0);
        $this->_resultsPerPage = $this->_request->query->get('resultsperpage', 20);
        $this->_verbose        = $this->_request->query->get('verbose', false);
        
        // Check verbosity
        if ($this->_verbose === 'yes') {
            $this->_verbose = true;
        }

        // Determine which view format to use
        $templateConfig = Utility::extractValue($this->_config, 'template', array());
        $acceptedContentTypes = $this->_request->getAcceptableContentTypes();
        $format = $this->_request->get('format', $acceptedContentTypes[0]);
        switch ($format) {
            case 'html':
            case 'text/html':
                $this->_view = new HtmlView($templateConfig);
                break;
            case 'xml':
            case 'application/xml':
                $this->_view = new XmlView($templateConfig);
                break;
            case 'json':
            case 'application/json':
            default:
                $callback = $this->_request->query->get('callback');
                if ($callback != null) {
                    $this->_view = new JsonPView($templateConfig, $callback);
                } else {
                    $this->_view = new JsonView($templateConfig);
                }
                break;
        }
    }

    protected function getStart() {
        return $this->_start;
    }
    
    protected function getResultsPerPage() {
        return $this->_resultsPerPage;
    }
    
    protected function getVerbosity() {
        return $this->_verbose;
    }
    
    protected function getView() {
        return $this->_view;
    }
    
    protected function getInputs() {
        $inputs = $this->_request->query->all();
        $inputs = array_merge($inputs, $this->_request->request->all());
        $inputs = array_merge($inputs, $this->_request->attributes->all());
        return $inputs;
    }
    
    protected function createResponseObject($data, $messages = array()) {
        $response = array();
        $response['response'] = $data;
        $response['messages'] = $messages;
        
        // Add metadata for response
        if ($data === false) {
            $count = 0;
        } else {
            $count = count(reset($data));
        }
        $response['meta']['count'] = $count;
        
        // If count is empty, add an information message
        if ($count == 0) {
            $response['messages'][] = array(
                'code' => 20001,
                'level' => 'warning',
                'text' => 'No records were found'
            );
        }
        
        return $response;
    }
    
    /**
     * Retrieve default values for mandatory audit trail inputs
     * 
     * @return array Contains values for 'updateBy', 'updateDatetime' and 'transactionSource'
     */
    protected function _getAuditTrailValues() {
        $values = array(
            'updateBy' => 4, // TODO: Need to actually use current user ID
            'updateDatetime' => Carbon::now()->toDateTimeString(),
            'transactionSource' => EnumTransactionSource::API_v1
        );
        return $values;
    }

    
    
    protected function getChildEntities($parentId, $verbose, $entityType = null, $entitySubType = null) {
        // Validate inputs
        $parentId = (int)$parentId;
        
        // Set up array of related entities
        $related = array();
        
        // Use default mapper to retrieve the types of child entities
        $mapper = $this->getBusinessObjectMapper();
        $entityTypes = $mapper->getRelatedEntityTypes($parentId, 'child', $entityType, $entitySubType);
        if ($entityTypes === false || count($entityTypes) <= 0) {
            return $related;
        }
        
        // Loop through entity types, and retrieve entity lists as required
        $singleEntity = false;
        foreach ($entityTypes as $type) {
            $mapper = $this->getBusinessObjectMapper($type->type);
            switch($type->type) {
                case 'address':
                    // One-to-many relationship - retrieve entire list
                    $result = $mapper->getAddressList($parentId, 0, 0, $verbose);
                    $singleEntity = false;
                    break;
                case 'user':
                    // One-to-one relationship - only include single entity
                    $result = $mapper->getUserByParentId($parentId, $verbose);
                    $singleEntity = true;
                    break;
            }
            
            // If the result was not null, add the related entities
            if ($result !== null && $singleEntity === false) {
                $related = array_merge($related, $result->getContent());
            } else if ($result !== null && $singleEntity === true) {
                // Extract single entity
                $content = $result->getContent();
                $details = array_shift($content);
                $related[$type->type] = $details[0];
            }
        }
        
        return $related;
    }
    
    
    protected function getBusinessObjectMapper($entityType = null, $entitySubType = null) {
        if ($entityType == null) {
            $classname = 'BusinessObjectMapper';
        } else {
            $classname = ucfirst($entityType).ucfirst($entitySubType).'Mapper';
        }
        
        // If we already have an instance of the mapper, return it immediately
        if (in_array($classname, $this->_mappers)) {
            return $this->_mappers[$classname];
        }
        
        // Create a new instance, add to collection and return
        if (!class_exists($classname)) {
            throw new Exception('Business object mapper does not exist for entity type ("'.$entityType.'") or entity sub-type ("'.$entitySubType.'")');
        }
        
        // Create new mapper
        $this->_mappers[$classname] = new $classname($this->_config, $this->_db, $this->_log);
        return $this->_mappers[$classname];
    }
    
    protected function _renderResponse($response, $description) {
        // Put updated people list back into the response and render
        $view = $this->getView();
        $view->setHttpStatusCode($response->getResponseCode());
        $view->responseBody = $response->toArray();
        $view->heading = $this->_name;
        $view->subHeading = $description;
        return $view->render();
    }
}