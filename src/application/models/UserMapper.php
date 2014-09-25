<?php

/**
 * User model
 *
 * @uses BusinessObjectMapper
 * @package API
 * @author Andrew Patterson <patto@live.com.au>
 */

use \Tranquility\Utility as Utility;
use \Tranquility\Response as Response;
use \Tranquility\Enum\System\MessageLevel as EnumMessageLevel;
use \Tranquility\Enum\System\HttpStatusCode as EnumStatusCodes;
use \Tranquility\Enum\System\EntityType as EnumEntityType;

class UserMapper extends BusinessObjectMapper {
    
    /**
     * Default mapping for column names to API field names
     * 
     * @return array Keys are API fields, and values
     */
    public function getDefaultFields() {
        $fields = array(
            'id' => 'id',
            'username' => 'username',
            'timezone' => 'timezone',
            'locale' => 'locale',
            'securityRole' => 'aclGroup',
            'registeredDate' => 'registeredDate',
            'lastVisitDate' => 'lastVisitDate'
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
            'username' => 'username',
            'timezone' => 'timezone',
            'locale' => 'locale',
            'securityRole' => 'aclGroup',
            'registeredDate' => 'registeredDate',
            'lastVisitDate' => 'lastVisitDate',
            'auditTransactionId' => 'transactionId',
            'auditTransactionSource' => 'transactionSource',
            'auditUpdateBy' => 'updateBy',
            'auditUpdateDatetime' => 'updateDatetime',
            'auditUpdateReason' => 'updateReason'
        );
        return $fields;
    }
    
    /**
     * List of mandatory fields required to update a User record
     * 
     * @param bool $newEntity
     * @return array
     */
    public function getMandatoryFields($newEntity = false) {
        $fields = array(
            'username',
            'timezone',
            'locale',
            'securityRole'
        );
        
        // Some fields are only mandatory for new entities
        if ($newEntity) {
            $fields[] = 'parentId';
            $fields[] = 'password';
                    
        }
        return $fields;
    }
    
    /**
     * Retrieves a full list of users (limited by paging parameters)
     * 
     * @param int   $resultsPerPage
     * @param int   $start
     * @param bool  $verbose
     * @param array $filter
     * @return mixed Array if successful, false on error
     */
    public function getUsersList($resultsPerPage, $start, $verbose = false, $filter = null) {
        // Default order is by user ID
        $order = 'users.id';
        
        // Retrieve the list
        $results = $this->_getUsers($resultsPerPage, $start, $filter, $order);
        
        // Set up response
        $response = new Response();
        $response->setResponseCode(EnumStatusCodes::OK);
        $response->setContent($this->transformResults($results, $verbose));
        if (count($results) > 0) {
            $response->addMessage(20200, 'message_20200_users_list_retrieved_sucessfully', EnumMessageLevel::Info);
        } else {
            $response->addMessage(10000, 'message_10000_no_records_returned', EnumMessageLevel::Warning);
        }
        
        // Return response
        return $response;
    }
    
    /**
     * Retrieves a single user by ID
     * 
     * @param int  $id
     * @param bool $verbose
     * @return mixed Array if successful, false on error
     */
    public function getUserById($id, $verbose = false) {
        $filter = array();
        $filter[] = array(
            'column' => 'users.id',
            'operator' => '=',
            'value' => $id
        );
        
        // Retrieve the person
        $results = $this->_getUsers(1, 0, $filter);
        
        // Set up response
        $response = new Response();
        $response->setResponseCode(EnumStatusCodes::OK);
        $response->setContent($this->transformResults($results, $verbose));
        if (count($results) > 0) {
            $response->addMessage(20201, 'message_20201_single_user_retrieved_successfully', EnumMessageLevel::Info);
        } else {
            $response->addMessage(10000, 'message_10000_no_records_returned', EnumMessageLevel::Warning);
        }
        
        // Return response
        return $response;
    }
    
    /**
     * Retrieves a single user by username
     * 
     * @param int  $id
     * @param bool $verbose
     * @return mixed Array if successful, false on error
     */
    public function getUserByUsername($username, $verbose = false) {
        $filter = array();
        $filter[] = array(
            'column' => 'users.username',
            'operator' => '=',
            'value' => $username
        );
        
        // Retrieve the person
        $results = $this->_getUsers(1, 0, $filter);
        
        // Set up response
        $response = new Response();
        $response->setResponseCode(EnumStatusCodes::OK);
        $response->setContent($this->transformResults($results, $verbose));
        if (count($results) > 0) {
            $response->addMessage(20201, 'message_20201_single_user_retrieved_successfully', EnumMessageLevel::Info);
        } else {
            $response->addMessage(10000, 'message_10000_no_records_returned', EnumMessageLevel::Warning);
        }
        
        // Return response
        return $response;
    }
    
    /**
     * Retrieves a single user based on their parent entity ID
     * 
     * @param int  $parentId
     * @param bool $verbose
     * @return mixed Array if successful, false on error
     */
    public function getUserByParentId($parentId, $verbose = false) {
        $query  = "SELECT entity.*, users.*, trans.* \n";
        $query .= "  FROM tql_entity AS entity, \n";
        $query .= "       tql_entity_users AS users, \n";
        $query .= "       tql_entity_xref AS xref, \n";
        $query .= "       tql_sys_trans_audit AS trans \n";
        $query .= " WHERE entity.id = users.id \n";
        $query .= "   AND entity.id = xref.childId \n";
        $query .= "   AND users.transactionId = trans.transactionId \n";
        $query .= "   AND entity.deleted = 0 \n";
        $query .= "   AND entity.type = ? \n";
        $query .= "   AND xref.parentId = ?";
        
        // Prepare variables
        $values = array(
            EnumEntityType::User,
            (int)$parentId
        );
        
        // Prepare and execute query
        $results = $this->_db->select($query, $values);
        
        // Set up response
        $response = new Response();
        $response->setResponseCode(EnumStatusCodes::OK);
        if (is_array($results) > 0) {
            $response->setContent($this->transformResults($results, $verbose));
            $response->addMessage(20201, 'message_20201_single_user_retrieved_successfully', EnumMessageLevel::Info);
        }
        
        // Return response
        return $response;
    }
    
    /**
     * Creates a new user record. In addition to the normal mandatory fields,
     * the parent entity ID and a password must be provided as inputs.
     * 
     * @param array $values
     * @param boolean $verbose
     * @return \Tranquility\Response\Response
     */
    public function createUser($values, $verbose) {
        Log::debug('Start of UserMapper::createUser() method');
        
        // Validate input fields (audit trail, mandatory fields, and value checks)
        $response = $this->validateInputFields($values, true);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // One or more fields falied validation - return immediately
            return $response;
        } 
        
        // Hash password string
        $password = Utility::extractValue($values, 'password', '');
        $secret = Hash::make($password);
        
        // Start new transaction
        $this->_db->beginTransaction();
        
        // Create new transaction record
        $transactionId = $this->_createTransactionRecord($values);
        
        // Create new entity record
        $id = $this->_createEntityRecord(EnumEntityType::User);
        
        // Add user data to table
        $query  = "INSERT INTO tql_entity_users (id, username, password, timezone, locale, active, aclGroup, registeredDate, lastVisitDate, transactionId) ";
        $query .= "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $inserts = array(
            $id,
            Utility::extractValue($values, 'username', ''),
            $secret,
            Utility::extractValue($values, 'timezone', ''),
            Utility::extractValue($values, 'locale', ''),
            Utility::extractValue($values, 'active', 1),
            Utility::extractValue($values, 'securityRole', 0),
            Carbon::now()->toDateTimeString(),                     // Set registered date to now (in UTC)
            Utility::getDbMinDateTime(),                           // Set last visit date to minimum date
            $transactionId
        );
        $this->_db->insert($query, $inserts);
        
        // Create cross reference entry to link user to parent
        $parentId = Utility::extractValue($values, 'parentId', 0);
        $this->_createEntityXrefRecord($parentId, $id, EnumEntityType::User, null, $transactionId);
        
        // Commit transaction
        $this->_db->commit();
        
        // Retrieve the person
        $response = $this->getUserById($id, $verbose);
        $response->clearMessages();
        $response->addMessage(20202, 'message_20202_new_user_created_successfully', EnumMessageLevel::Success);
        $response->addTransactionId($transactionId);
        return $response;
    }

    /**
     * Updates an existing user record
     * 
     * @param type $values
     * @param type $verbose
     * @return \Tranquility\Response\Response
     */
    public function updateUser($values, $verbose) {
        Log::debug('Start of UserMapper::updateUser() method');
        
        // Validate input fields (audit trail, mandatory fields, and value checks)
        $response = $this->validateInputFields($values);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // One or more fields falied validation - return immediately
            return $response;
        } 
        
        // Check user entity exists
        $id = Utility::extractValue($values, 'id', 0);
        if (!$this->_checkEntityExists($id, EnumEntityType::User)) {
            // Supplied ID is not a user, does not exist, or has been marked as deleted
            $response->setResponseCode(EnumStatusCodes::OK);
            $response->addMessage(10003, 'message_10003_specified_entity_does_not_exist', EnumMessageLevel::Error);
            return $response;
        }
        // End service input validation
        
        // If a password has been provided, hash it now
        $password = Utility::extractValue($values, 'password', '');
        if ($password != '') {
            $password = Hash::make($password);
        }
        
        // Start new transaction
        $this->_db->beginTransaction();
        
        // Create new transaction record
        $transactionId = $this->_createTransactionRecord($values);
        
        // Create historical record
        $result = $this->_createHistoricalUserRecord($id);
        if ($result == false) {
            $this->_db->rollBack();
            $response->setResponseCode(EnumStatusCodes::InternalServerError);
            $response->addMessage(10004, 'message_10004_unable_to_create_historical_entity_record', EnumMessageLevel::Error);
            return $response;
        }
        
        // Increment entity version
        $result = $this->_incrementEntityVersion($id);
        if ($result == false) {
            $this->_db->rollBack();
            $response->setResponseCode(EnumStatusCodes::InternalServerError);
            $response->addMessage(20204, 'message_20204_unable_to_update_user_record', EnumMessageLevel::Error);
            return $response;
        }
        
        // Update main record with new details
        $query  = "UPDATE tql_entity_users as users \n";
        $query .= "SET users.username = ?, users.timezone = ?, users.locale = ?, users.active = ?, users.aclGroup = ?, users.transactionId = ? \n";
        $updates = array(
            Utility::extractValue($values, 'username', ''),
            Utility::extractValue($values, 'timezone', ''),
            Utility::extractValue($values, 'locale', ''),
            Utility::extractValue($values, 'active', 0),
            Utility::extractValue($values, 'securityRole', 0),
            $transactionId
        );
        
        // If password is being updated, add it to the SQL statement now
        if ($password != '') {
            $query .= ", users.password = ? \n";
            $updates[] = $password;
        }
        
        // Complete query
        $query .= "WHERE id = ? ";
        $updates[] = $id;

        $result = $this->_db->update($query, $updates);
        if ($result == false) {
            $this->_db->rollBack();
            $response->setResponseCode(EnumStatusCodes::InternalServerError);
            $response->addMessage(20204, 'message_20204_unable_to_update_user_record', EnumMessageLevel::Error);
            return $response;
        }        
        
        // Commit transaction
        $this->_db->commit();
        
        // Build success response
        $response = $this->getUserById($id, $verbose);
        $response->clearMessages();
        $response->setResponseCode(EnumStatusCodes::OK);
        $response->addMessage(20205, 'message_20205_user_updated_successfully', EnumMessageLevel::Success);
        $response->addTransactionId($transactionId);
        return $response;
    }
    
    /**
     * Logically deletes the user record for the supplied ID
     * 
     * @param array $values
     * @param boolean $verbose
     * @return \Tranquility\Response\Response
     */
    public function deleteUser($values, $verbose) {
        Log::debug('Start of UserMapper::deleteUser() method');
        
        // Validate audit trail fields only 
        $response = $this->_validateAuditTrailValues($values);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // One or more fields falied validation - return immediately
            return $response;
        } 
        
        // Check user entity exists
        $id = Utility::extractValue($values, 'id', 0);
        if (!$this->_checkEntityExists($id, EnumEntityType::User)) {
            // Supplied ID is not a user, does not exist, or has already been deleted
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
        $result = $this->_createHistoricalUserRecord($id);
        if ($result == false) {
            $this->_db->rollBack();
            $response->setResponseCode(EnumStatusCodes::InternalServerError);
            $response->addMessage(10004, 'message_10004_unable_to_create_historical_entity_record', EnumMessageLevel::Error);
            return $response;
        }
        
        // Mark record as logically deleted and increment version number
        $this->_deleteEntityRecord($id);
        
        // Update current record with audit trail details
        $query  = "UPDATE tql_entity_users AS users \n";
        $query .= "SET users.transactionId = ? \n";
        $query .= "WHERE users.id = ?";
        $values = array($transactionId, $id);
        $result = $this->_db->update($query, $values);
        if ($result <= 0) {
            $this->_db->rollBack();
            $response->setResponseCode(EnumStatusCodes::InternalServerError);
            $response->addMessage(20206, 'message_20206_unable_to_delete_user_record', EnumMessageLevel::Error);
            return $response;
        }
        
        // Commit transaction
        $this->_db->commit();
        
        // Build success response
        $response->clearMessages();
        $response->setResponseCode(EnumStatusCodes::OK);
        $response->addMessage(20207, 'message_20207_user_deleted_successfully', EnumMessageLevel::Success);
        $response->addTransactionId($transactionId);
        return $response;
    }
    
    /**
     * Helper method that performs actual retrieval of user records
     * 
     * @param int    $resultsPerPage
     * @param int    $start
     * @param array  $filter
     * @param string $order
     * @return boolean Array if successful, false on error
     */
    protected function _getUsers($resultsPerPage, $start, $filter = null, $order = null) {
        // Construct SQL statement to retrieve list of people
        $query  = "SELECT entity.*, users.*, trans.* \n";
        $query .= "  FROM tql_entity AS entity, \n";
        $query .= "       tql_entity_users AS users, \n";
        $query .= "       tql_sys_trans_audit AS trans \n";
        $query .= " WHERE entity.id = users.id \n";
        $query .= "   AND users.transactionId = trans.transactionId \n";
        $query .= "   AND entity.type = 'user' \n";
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
        $results = $this->_db->select($query, $filterValues);
        return $results;
    }
    
    /**
     * Helper method that copies the current user record into a history table
     * @param int $id User ID
     * @return type
     */
    protected function _createHistoricalUserRecord($id) {
        // Copy existing record into historical table
        $query  = "INSERT INTO tql_history_users (id, version, username, password, timezone, locale, active, aclGroup, registeredDate, lastVisitDate, transactionId) \n";
        $query .= "SELECT a.id, a.version, b.username, b.password, b.timezone, b.locale, b.active, b.aclGroup, b.registeredDate, b.lastVisitDate, b.transactionId \n";
        $query .= "FROM tql_entity AS a, \n";
        $query .= "     tql_entity_users AS b \n";
        $query .= "WHERE a.id = ? \n";
        $query .= "AND a.id = b.id ";
        return $this->_db->insert($query, array($id));
    }
    
    /**
     * Validate all inputs for creating or updating a user record
     * 
     * @param array $inputs
     * @param boolean $newRecord
     * @param Response $response
     */
    public function validateInputFields($inputs, $newRecord = false, $validateAuditTrail = true) {
        // Validate audit trail and mandatory fields
        $response = parent::validateInputFields($inputs, $newRecord, $validateAuditTrail);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // If a mandatory field has not been provided, there is no point going further
            return $response;
        }
        
        // Validate timezone
        $refDataMapper = new ReferenceDataMapper($this->_db);
        $timezone = Utility::extractValue($inputs, 'timezone', Config::get('app.timezone'));
        if (!$refDataMapper->isValidCode($timezone, 'tql_cd_timezones', 'timezone')) {
            $response->addMessage(10006, 'message_10006_invalid_timezone_identifier: '.$timezone, EnumMessageLevel::Error, 'timezone');
        }
        
        // Validate locale
        $locale = Utility::extractValue($inputs, 'locale', Config::get('app.locale'));
        if (!$refDataMapper->isValidCode($locale, 'tql_cd_locales', 'locale')) {
            $response->addMessage(10007, 'message_10007_invalid_locale_identifier: '.$locale, EnumMessageLevel::Error, 'locale');
        }
        
        // Validate security role
        $securityRoleMapper = new SecurityRoleMapper($this->_db);
        $securityRole = Utility::extractValue($inputs, 'securityRole', 0);
        $result = $securityRoleMapper->getSecurityRoleById($securityRole);
        if ($result->getItemCount() <= 0) {
            $response->addMessage(20208, 'message_20208_invalid_security_role_id: '.$locale, EnumMessageLevel::Error, 'securityRole');
        }
        
        // Additional fields require validation for new records
        if ($newRecord) {
            // Make sure a parent entity ID has been provided and is valid
            $parentId = Utility::extractValue($inputs, 'parentId', 0);
            if ($this->_checkEntityExists($parentId) === false) {
                // Parent entity does not exist, or has been logically deleted
                $response->addMessage(10005, 'message_10005_unable_to_locate_specified_parent_entity', EnumMessageLevel::Error);
            }

            // Check if username has already been used for another user account
            $username = Utility::extractValue($inputs, 'username', '');
            $result = $this->getUserByUsername($username);
            if ($result->getItemCount() > 0) {
                // Username is already in use
                $response->addMessage(20203, 'message_20203_username_already_in_use', EnumMessageLevel::Error);
            }
        }
        
        if ($response->getMessageCount() > 0) {
            $response->setResponseCode(EnumStatusCodes::BadRequest);
        }
        
        return $response;
    }
    
    /**
     * Transforms the initial set of results into a friendlier format for 
     * presentation
     * 
     * @param array $results
     * @param boolean $verbose
     * @return array
     */
    public function transformResults($results, $verbose, $customFields = array()) {
        $results = parent::transformResults($results, $verbose, $customFields);
        
        $output = array();
        $output['users'] = $results;
        return $output;
    }
}
