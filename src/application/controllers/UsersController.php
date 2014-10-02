<?php

class UsersController extends BaseController {
    
    protected $_mapper = null;
    protected $_name   = 'Users';
    
    public function __construct($request, $config, $db, $log) {
        parent::__construct($request, $config, $db, $log);
        
        // Initialise UsersMapper class (as it does most of the heavy lifting)
        $this->_mapper = new UsersMapper($config, $this->_db, $this->_log);
    }
    
    public function retrieveUsersList() {
        $start = $this->getStart();
        $resultsPerPage = $this->getResultsPerPage();
        $verbose = $this->getVerbosity();

        // Retrieve list of users
        $response = $this->_retrieveUsers($resultsPerPage, $start, $verbose);
        return $this->_renderResponse($response, 'Retrieve list of users');
    }
    
    public function retrieveUserDetails($id) {
        $verbose = $this->getVerbosity();
        
        // Retrieve single user
        $response = $this->_retrieveUsers(1, 0, $verbose, $id);
        return $this->_renderResponse($response, 'Details of an individual user account');
    }
    
    public function retrieveUserDetailsForParent($parentId) {
        $verbose = $this->getVerbosity();
        
        // Retrieve single user
        $response = $this->_mapper->getUserByParentId($parentId, $verbose);
        return $this->_renderResponse($response, 'Details of an individual user account');
    }
    
    public function createUser() {
        $values = array_merge($this->getInputs(), $this->_getAuditTrailValues());
        $response = $this->_mapper->createUser($values, $this->getVerbosity());
        return $this->_renderResponse($response, 'Create a new user account');
    }
    
    public function createUserForParent($parentId) {
        $values = array_merge(array('parentId' => $parentId), $this->getInputs(), $this->_getAuditTrailValues());
        $response = $this->_mapper->createUser($values, $this->getVerbosity());
        return $this->_renderResponse($response, 'Create a new user account for an existing entity');
    }
    
    public function updateUser($id) {
        $values = array_merge(array('id' => $id), $this->getInputs(), $this->_getAuditTrailValues());
        $response = $this->_mapper->updateUser($values, $this->getVerbosity());
        return $this->_renderResponse($response, 'Update details for a user account');
    }
    
    public function deleteUser($id) {
        $values = array_merge(array('id' => $id), $this->getInputs(), $this->_getAuditTrailValues());
        $response = $this->_mapper->deleteUser($values, $this->getVerbosity());
        return $this->_renderResponse($response, 'Delete an existing user account');
    }
    
    private function _retrieveUsers($resultsPerPage, $start, $verbose, $id = 0) {
        if ($id === 0) {
            // We are fetching a list of users - check for filter conditions
            $filters = array();
            if ($this->_request->query->has('search')) {
                $filters['search'] = $this->_request->query->get('search');
            }
            if ($this->_request->query->has('username')) {
                $filters['username'] = $this->_request->query->get('username');
            }
            
            // Retrieve a list of users
            $response = $this->_mapper->getUsersList($resultsPerPage, $start, $verbose, $filters);
        } else {
            // Assume ID is an actual id number - retrieve single user
            $response = $this->_mapper->getUserById($id, $verbose);
        }
        
        // If any errors were encoutered, return immediately
        if ($response->hasErrors()) {
            return $this->getView()->render($response, $response->getResponseCode());
        }
        
        return $response;
    }
}