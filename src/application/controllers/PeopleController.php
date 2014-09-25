<?php

class PeopleController extends BaseController {
    
    protected $_mapper = null;
    protected $_name   = 'People';
    
    public function __construct($request, $config, $db, $log) {
        parent::__construct($request, $config, $db, $log);
        
        // Initialise PeopleMapper class (as it does most of the heavy lifting)
        $this->_mapper = new PeopleMapper($config, $this->_db, $this->_log);
    }
    
    public function retrievePeopleList() {
        $start = $this->getStart();
        $resultsPerPage = $this->getResultsPerPage();
        $verbose = $this->getVerbosity();

        // Retrieve list of people
        $response = $this->_retrievePeople($resultsPerPage, $start, $verbose);
        return $this->_renderResponse($response, 'Retrieve list of people');
    }
    
    public function retrievePersonDetails($id) {
        $verbose = $this->getVerbosity();
        
        // Retrieve single person
        $response = $this->_retrievePeople(1, 0, $verbose, $id);
        return $this->_renderResponse($response, 'Details of an individual person');
    }
    
    public function createPerson() {
        $values = array_merge($this->getInputs(), $this->_getAuditTrailValues());
        $response = $this->_mapper->createPerson($values, $this->getVerbosity());
        return $this->_renderResponse($response, 'Create a new person record');
    }
    
    public function updatePerson($id) {
        $values = array_merge(array('id' => $id), $this->getInputs(), $this->_getAuditTrailValues());
        $response = $this->_mapper->updatePerson($values, $this->getVerbosity());
        return $this->_renderResponse($response, 'Update details for a person');
    }
    
    public function deletePerson($id) {
        $values = array_merge(array('id' => $id), $this->getInputs(), $this->_getAuditTrailValues());
        $response = $this->_mapper->deletePerson($values, $this->getVerbosity());
        return $this->_renderResponse($response, 'Delete record for this person');
    }
    
    private function _retrievePeople($resultsPerPage, $start, $verbose, $id = 0) {
        if ($id === 0) {
            // Retrieve a list of people
            $response = $this->_mapper->getPeopleList($resultsPerPage, $start, $verbose);
        } else if (!is_numeric($id)) {
            // ID is not numeric - assume it is a username
            $response = $this->_mapper->getPersonByUsername($id, $verbose);
        } else {
            // Assume ID is an actual id number - retrieve single person
            $response = $this->_mapper->getPersonById($id, $verbose);
        }
        
        // If any errors were encoutered, return immediately
        if ($response->hasErrors()) {
            return $this->getView()->render($response, $response->getResponseCode());
        }
        
        // For each person, retrieve their set of addresses and security group details
        $list = $response->getContent();
        for ($i = 0; $i < count($list['people']); $i++) {
            // Get the list of child entities for this person
            $personId = $list['people'][$i]['id'];
            $list['people'][$i]['relatedEntities'] = $this->getChildEntities($personId, $verbose);
        }
        $response->setContent($list);
        return $response;
    }
}