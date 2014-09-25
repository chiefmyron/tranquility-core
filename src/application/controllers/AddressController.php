<?php

class AddressController extends BaseController {
    
    protected $_mapper = null;
    protected $_name   = 'Addresses';
    
    public function __construct($request, $config, $db, $log) {
        parent::__construct($request, $config, $db, $log);
        $this->_mapper = new AddressMapper($config, $db, $log);
    }
    
    public function retrieveAddressList($parentId) {
        $start = $this->getStart();
        $resultsPerPage = $this->getResultsPerPage();
        $verbose = $this->getVerbosity();
        
        // Check for any filters
        $filter = $this->_request->query->get('filter');
        $type = $this->_request->query->get('type', null);
        if ($type != null) {
            $type = explode(",", $type);
        }
        
        $response = $this->_mapper->getAddressList($parentId, $resultsPerPage, $start, $verbose, $filter, $type);
        return $this->_renderResponse($response, 'Retrieve list of address for an entity');
    }
    
    public function retrieveAddressDetails($parentId, $addressId, $type) {
        $verbose = $this->getVerbosity();
        
        // Retrieve single person
        $response = $this->_mapper->getAddressById($parentId, $addressId, $type);
        return $this->_renderResponse($response, 'Retrieve a specific address for an entity');
    }
    
    public function createAddress() {
        $values = array_merge($this->getInputs(), $this->_getAuditTrailValues());
        $response = $this->_mapper->createAddress($values, $this->getVerbosity());
        return $this->_renderResponse($response, 'Create a new address record');
    }
    
    public function updateAddress($parentId, $id) {
        $values = array_merge(array('parentId' => $parentId, 'id' => $id), $this->getInputs(), $this->_getAuditTrailValues());
        $response = $this->_mapper->updateAddress($values, $this->getVerbosity());
        return $this->_renderResponse($response, 'Update an existing address');
    }
    
    public function deleteAddress($parentId, $id) {
        $values = array_merge(array('parentId' => $parentId, 'id' => $id), $this->getInputs(), $this->_getAuditTrailValues());
        $response = $this->_mapper->deleteAddress($values, $this->getVerbosity());
        return $this->_renderResponse($response, 'Delete record for this address');
    }
}