<?php

/**
 * People model
 *
 * @uses BusinessObjectMapper
 * @package API
 * @author Andrew Patterson <patto@live.com.au>
 */

use \Tranquility\Utility as Utility;
use \Tranquility\Response as Response;
use \Tranquility\Enum\System\MessageLevel as EnumMessageLevel;
use \Tranquility\Enum\System\HttpStatusCode as EnumStatusCodes;

class PeopleMapper extends BusinessObjectMapper {
    
    /**
     * Default mapping for column names to API field names
     * 
     * @return array Keys are API fields, and values
     */
    public function getDefaultFields() {
        $fields = array(
            'id' => 'id',
            'title' => 'title',
            'firstName' => 'firstName',
            'lastName' => 'lastName',
            'position' => 'position'
        );
        return $fields;
    }
    
    /**
     * Full list of field mappings for verbose calls
     * Contains all fields in the default 
     * @return array
     */
    public function getVerboseFields() {
        $fields = array(
            'id' => 'id',
            'version' => 'version',
            'title' => 'title',
            'firstName' => 'firstName',
            'lastName' => 'lastName',
            'position' => 'position',
            'auditTransactionId' => 'transactionId',
            'auditTransactionSource' => 'transactionSource',
            'auditUpdateBy' => 'updateBy',
            'auditUpdateDatetime' => 'updateDatetime',
            'auditUpdateReason' => 'updateReason'
        );
        return $fields;
    }
    
    /**
     * Returns field IDs for mandatory fields required to create / update People 
     * objects
     * 
     * @return array
     */
    public function getMandatoryFields($newRecord = false) {
        $fields = array(
            'title',
            'firstName',
            'lastName'
        );
        return $fields;
    }
    
    /**
     * Retrieves a full list of people (limited by paging parameters)
     * 
     * @param int   $resultsPerPage
     * @param int   $start
     * @param bool  $verbose
     * @param array $filter
     * @return mixed Array if successful, false on error
     */
    public function getPeopleList($resultsPerPage, $start, $verbose = false, $filter = null) {
        // Default order is by person ID
        $order = 'people.id';
        
        // Retrieve the list
        $results = $this->_getPeople($resultsPerPage, $start, $filter, $order);
        $peopleList = $this->transformResults($results, $verbose);
        $peopleList = $this->_addChildEntities($peopleList, $verbose);
        
        // Set up response
        $response = new Response();
        $response->setResponseCode(EnumStatusCodes::OK);
        $response->setContent($peopleList);
        if (count($results) > 0) {
            $response->addMessage(20000, 'message_20000_people_list_retrieved_sucessfully', EnumMessageLevel::Info);
        } else {
            $response->addMessage(10000, 'message_10000_no_records_returned', EnumMessageLevel::Warning);
        }
        
        // Return response
        return $response;
    }
    
    /**
     * Retrieves a single person 
     * 
     * @param int  $id
     * @param bool $verbose
     * @return mixed Array if successful, false on error
     */
    public function getPersonById($id, $verbose = false) {
        $filter = array();
        $filter[] = array(
            'column' => 'people.id',
            'operator' => '=',
            'value' => $id
        );
        
        // Retrieve the person
        $results = $this->_getPeople(1, 0, $filter);
        $peopleList = $this->transformResults($results, $verbose);
        $peopleList = $this->_addChildEntities($peopleList, $verbose);
        
        // Set up response
        $response = new Response();
        $response->setResponseCode(EnumStatusCodes::OK);
        $response->setContent($peopleList);
        if (count($results) > 0) {
            $response->addMessage(20001, 'message_20001_single_person_retrieved_successfully', EnumMessageLevel::Info);
        } else {
            $response->addMessage(10000, 'message_10000_no_records_returned', EnumMessageLevel::Warning);
        }
        
        // Return response
        return $response;
    }
    
    /**
     * Creates a new person record
     * 
     * @param array $values
     * @param boolean $verbose
     * @return \Tranquility\Response\Response
     */
    public function createPerson($values, $verbose) {
        $this->_log->debug('Start of PeopleMapper::createPerson() method');
        
        // Validate input fields (audit trail, mandatory fields, and value checks)
        $response = $this->validateInputFields($values, true);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // One or more fields falied validation - return immediately
            return $response;
        } 
        
        // Start new transaction
        $this->_db->beginTransaction();
        
        // Create new transaction record
        $transactionId = $this->_createTransactionRecord($values);
        
        // Create new entity record
        $id = $this->_createEntityRecord('person');
        
        // Add person data to table
        $query  = "INSERT INTO tql_entity_people (id, title, firstName, lastName, position, transactionId) ";
        $query .= "VALUES (?, ?, ?, ?, ?, ?)";
        $inserts = array(
            $id,
            Utility::extractValue($values, 'title', ''),
            Utility::extractValue($values, 'firstName', ''),
            Utility::extractValue($values, 'lastName', ''),
            Utility::extractValue($values, 'position', ''),
            $transactionId
        );
        $this->_db->insert($query, $inserts);
        
        // Commit transaction
        $this->_db->commit();
        
        // Retrieve the person
        $response = $this->getPersonById($id, $verbose);
        $response->clearMessages();
        $response->addMessage(20002, 'message_20002_new_person_created_successfully', EnumMessageLevel::Success);
        $response->addTransactionId($transactionId);
        return $response;
    }
    
    /**
     * Updates an existing person record
     * 
     * @param type $values
     * @param type $verbose
     * @return \Tranquility\Response\Response
     */
    public function updatePerson($values, $verbose) {
        $this->_log->debug('Start of PeopleMapper::updatePerson() method');
        
        // Validate input fields (audit trail, mandatory fields, and value checks)
        $response = $this->validateInputFields($values, true);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // One or more fields falied validation - return immediately
            return $response;
        } 
        
        // Check person entity exists
        $id = Utility::extractValue($values, 'id', 0);
        if (!$this->_checkEntityExists($id, 'person')) {
            // Supplied ID is not a person, does not exist, or is already deleted
            $response->setResponseCode(EnumStatusCodes::OK);
            $response->addMessage(10003, 'message_10003_specified_entity_does_not_exist', EnumMessageLevel::Error);
            return $response;
        }
        // End service input validation
        
        // Start new transaction
        $this->_db->beginTransaction();
        
        // Create new transaction record
        $transactionId = $this->_createTransactionRecord($values);
        
        // Create historical record
        $result = $this->_createHistoricalPersonRecord($id);
        if ($result == false) {
            $this->_db->rollback();
            $response->setResponseCode(EnumStatusCodes::InternalServerError);
            $response->addMessage(10004, 'message_10004_unable_to_create_historical_entity_record', EnumMessageLevel::Error);
            return $response;
        }
        
        // Increment entity version
        $result = $this->_incrementEntityVersion($id);
        if ($result == false) {
            $this->_db->rollback();
            $response->setResponseCode(EnumStatusCodes::InternalServerError);
            $response->addMessage(20005, 'message_20005_unable_to_update_person_record', EnumMessageLevel::Error);
            return $response;
        }
        
        // Update main record with new details
        $query  = "UPDATE tql_entity_people as people \n";
        $query .= "SET people.title = ?, people.firstName = ?, people.lastName = ?, people.position = ?, people.transactionId = ? \n";
        $query .= "WHERE id = ? ";
        $updates = array(
            Utility::extractValue($values, 'title', ''),
            Utility::extractValue($values, 'firstName', ''),
            Utility::extractValue($values, 'lastName', ''),
            Utility::extractValue($values, 'position', ''),
            $transactionId,
            $id
        );
        $result = $this->_db->update($query, $updates);
        if ($result == false) {
            $this->_db->rollback();
            $response->setResponseCode(EnumStatusCodes::InternalServerError);
            $response->addMessage(20005, 'message_20005_unable_to_update_person_record', EnumMessageLevel::Error);
            return $response;
        }        
        
        // Commit transaction
        $this->_db->commit();
        
        // Build success response
        $response = $this->getPersonById($id, $verbose);
        $response->clearMessages();
        $response->setResponseCode(EnumStatusCodes::OK);
        $response->addMessage(20006, 'message_20006_person_updated_successfully', EnumMessageLevel::Success);
        $response->addTransactionId($transactionId);
        return $response;
    }
    
    /**
     * Logically deletes the person record for the supplied ID
     * 
     * @param array $values
     * @param boolean $verbose
     * @return \Tranquility\Response\Response
     */
    public function deletePerson($values, $verbose) {
        $this->_log->debug('Start of PeopleMapper::deletePerson() method');
        
        // Validate audit trail fields only 
        $response = $this->_validateAuditTrailValues($values);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // One or more fields falied validation - return immediately
            return $response;
        } 
        
        // Check person entity exists
        $id = Utility::extractValue($values, 'id', 0);
        if (!$this->_checkEntityExists($id, 'person')) {
            // Supplied ID is not a person, does not exist, or is already deleted
            $response->setResponseCode(EnumStatusCodes::OK);
            $response->addMessage(10003, 'message_10003_specified_entity_does_not_exist', EnumMessageLevel::Error);
            return $response;
        }
        // End service input validation
        
        // Start new transaction
        $this->_db->beginTransaction();
        
        // Create new transaction record
        $transactionId = $this->_createTransactionRecord($values);
        
        // Create historical record
        $result = $this->_createHistoricalPersonRecord($id);
        if ($result == false) {
            $this->_db->rollback();
            $response->setResponseCode(EnumStatusCodes::InternalServerError);
            $response->addMessage(10004, 'message_10004_unable_to_create_historical_entity_record', EnumMessageLevel::Error);
            return $response;
        }
        
        // Mark record as logically deleted and increment version number
        $this->_deleteEntityRecord($id);
        
        // Update current record with audit trail details
        $query  = "UPDATE tql_entity_people AS people \n";
        $query .= "SET people.transactionId = ? \n";
        $query .= "WHERE people.id = ?";
        $values = array($transactionId, $id);
        $result = $this->_db->update($query, $values);
        if ($result <= 0) {
            $this->_db->rollback();
            $response->setResponseCode(EnumStatusCodes::InternalServerError);
            $response->addMessage(20003, 'message_20003_unable_to_delete_person_record', EnumMessageLevel::Error);
            return $response;
        }
        
        // Commit transaction
        $this->_db->commit();
        
        // Build success response
        $response->clearMessages();
        $response->setResponseCode(EnumStatusCodes::OK);
        $response->addMessage(20004, 'message_20004_person_deleted_successfully', EnumMessageLevel::Success);
        $response->addTransactionId($transactionId);
        return $response;
    }
    
    /**
     * Helper method that performs actual retrieval of people records
     * 
     * @param int    $resultsPerPage
     * @param int    $start
     * @param array  $filter
     * @param string $order
     * @return boolean Array if successful, false on error
     */
    protected function _getPeople($resultsPerPage, $start, $filter = null, $order = null) {
        // Construct SQL statement to retrieve list of people
        $query  = "SELECT entity.*, people.*, trans.* \n";
        $query .= "  FROM tql_entity AS entity, \n";
        $query .= "       tql_entity_people AS people, \n";
        $query .= "       tql_sys_trans_audit AS trans \n";
        $query .= " WHERE entity.id = people.id \n";
        $query .= "   AND people.transactionId = trans.transactionId \n";
        $query .= "   AND entity.type = 'person' \n";
        $query .= "   AND entity.deleted = 0 \n";
        
        // Add additional selection criteria
        $filterValues = array();
        if (is_array($filter)) {
            foreach ($filter as $item) {
                $query .= "AND ".$item['column']." ".$item['operator']." ? \n";
                $filterValues[] = $item['value'];
            }
        }
        
        // Add ordering if specified
        if ($order) {
            $query .= "ORDER BY ".$order." \n";
        }
        
        // Limit query
        $query .= $this->buildQueryLimitClause($resultsPerPage, $start);
        
        // Prepare and execute query
        $results = $this->_db->query($query, $filterValues);
        return $results;
    }
    
    
    protected function _addChildEntities($people, $verbose = false) {
        // For a person record, child entities are address list and user details
        $addressMapper = new AddressMapper($this->_config, $this->_db, $this->_log);
        $userMapper = new UserMapper($this->_config, $this->_db, $this->_log);
        
        foreach ($people['people'] as &$person) {
            // Retrieve address list
            $response = $addressMapper->getAddressList($person['id'], 0, 1, $verbose);
            if (!$response->hasErrors()) {
                $addresses = $response->getContent();
                $person['addresses'] = $addresses['addresses'];
            }
        }
        
        return $people;
    }
    
    /**
     * Helper method that copies the current user record into a history table
     * @param int $id Person ID
     * @return boolean
     */
    protected function _createHistoricalPersonRecord($id) {
        // Copy existing record into historical table
        $query  = "INSERT INTO tql_history_people (id, version, title, firstName, lastName, position, transactionId) \n";
        $query .= "SELECT a.id, a.version, b.title, b.firstName, b.lastName, b.position, b.transactionId \n";
        $query .= "FROM tql_entity AS a, \n";
        $query .= "     tql_entity_people AS b \n";
        $query .= "WHERE a.id = ? \n";
        $query .= "AND a.id = b.id ";
        return $this->_db->insert($query, array($id));
    }
    
    /**
     * Validate all inputs for creating or updating a user record
     * 
     * @param type $inputs
     * @param type $response
     */
    public function validateInputFields($inputs, $newRecord = false, $validateAuditTrail = true) {
        $response = parent::validateInputFields($inputs, $newRecord, $validateAuditTrail);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // If a mandatory field has not been provided, there is no point going further
            return $response;
        }
        
        // Validate title
        /*$refDataMapper = new ReferenceDataMapper($this->_db);
        $title = Utility::extractValue($inputs, 'title', '');
        if (!$refDataMapper->isValidCode($title, 'tql_cd_titles', 'code')) {
            $response->addMessage(10006, 'message_10006_invalid_timezone_identifier: '.$timezone, EnumMessageLevel::Error, 'timezone');
        }*/
        
        if ($response->getMessageCount() > 0) {
            $response->setResponseCode(EnumStatusCodes::BadRequest);
        }
        
        return $response;
    }
    
    public function transformResults($results, $verbose, $customFields = array()) {
        $results = parent::transformResults($results, $verbose, $customFields);
        
        $output = array();
        $output['people'] = $results;
        return $output;
    }
}
