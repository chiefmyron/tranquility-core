<?php

/**
 * Users model
 *
 * @uses BusinessObjectMapper
 * @package API
 * @author Andrew Patterson <patto@live.com.au>
 */

use \Carbon\Carbon as Carbon;

use \Tranquility\Utility as Utility;
use \Tranquility\Response as Response;
use \Tranquility\Enum\System\EntityType as EnumEntityType;
use \Tranquility\Enum\System\MessageLevel as EnumMessageLevel;
use \Tranquility\Enum\System\HttpStatusCode as EnumStatusCodes;


class UsersMapper extends BusinessObjectMapper {
    
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
    public function getUsersList($resultsPerPage, $start, $verbose = false, $filters = array()) {
        $this->_log->debug('Start of UsersMapper::getUsersList() method', $filters);

        // Default order is by user ID
        $order = 'users.id';
        
        // Retrieve the list
        $results = $this->_getUsers($resultsPerPage, $start, $filters, $order);
        
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
        $this->_log->debug('Start of UsersMapper::getUserById() method [id: '.$id.']');
        
        // Retrieve the user
        $filter = array('id' => $id);
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
     * @param string $username
     * @param bool   $verbose
     * @return mixed Array if successful, false on error
     */
    public function getUserByUsername($username, $verbose = false) {
        $this->_log->debug('Start of UsersMapper::getUserByUsername() method [username: '.$username.']');
        
        // Retrieve the user
        $filter = array('username' => $username);
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
        $this->_log->debug('Start of UsersMapper::getUserByParentId() method [Parent ID: '.$parentId.']');
        
        // Retrieve the user
        $query  = "SELECT entity.*, users.*, trans.* \n";
        $query .= "  FROM tql_entity AS entity, \n";
        $query .= "       tql_entity_users AS users, \n";
        $query .= "       tql_entity_xref AS xref, \n";
        $query .= "       tql_sys_trans_audit AS trans \n";
        $query .= " WHERE entity.id = users.id \n";
        $query .= "   AND entity.id = xref.childId \n";
        $query .= "   AND users.transactionId = trans.transactionId \n";
        $query .= "   AND entity.deleted = 0 \n";
        $query .= "   AND entity.type = :entityType \n";
        $query .= "   AND xref.parentId = :parentId";
        
        // Prepare variables
        $values = array(
            'entityType' => EnumEntityType::User,
            'parentId'   => (int)$parentId
        );
        
        // Prepare and execute query
        $results = $this->_db->select($query, $values);
        
        
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
     * Creates a new user record. In addition to the normal mandatory fields,
     * the parent entity ID and a password must be provided as inputs.
     * 
     * @param array   $values
     * @param boolean $verbose
     * @return \Tranquility\Response
     */
    public function createUser($values, $verbose) {
        $this->_log->debug('Start of UsersMapper::createUser() method', $values);
        
        // Validate input fields (audit trail, mandatory fields, and value checks)
        $response = $this->validateInputFields($values, true);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // One or more fields falied validation - return immediately
            return $response;
        } 
        
        // Hash password string
        $password = Utility::extractValue($values, 'password', '');
        $secret = Utility::makeHash($password);
        
        // Start new transaction
        $this->_db->beginTransaction();
        
        // Create new transaction record
        $transactionId = $this->_createTransactionRecord($values);
        
        // Create new entity record
        $id = $this->_createEntityRecord(EnumEntityType::User);
        $parentId = Utility::extractValue($values, 'parentId', 0);
        
        // Add user data to table
        $query  = "INSERT INTO tql_entity_users (id, username, password, timezone, locale, active, aclGroup, registeredDate, lastVisitDate, transactionId) ";
        $query .= "VALUES (:id, :username, :password, :timezone, :locale, :active, :aclGroup, :registeredDate, :lastVisitDate, :transactionId)";
        $inserts = array(
            'id'             => $id,
            'username'       => Utility::extractValue($values, 'username', ''),
            'password'       => $secret,
            'timezone'       => Utility::extractValue($values, 'timezone', ''),
            'locale'         => Utility::extractValue($values, 'locale', ''),
            'active'         => Utility::extractValue($values, 'active', 1),
            'aclGroup'       => Utility::extractValue($values, 'securityRole', 0),
            'registeredDate' => Carbon::now()->toDateTimeString(),                     // Set registered date to now (in UTC)
            'lastVisitDate'  => Utility::getDbMinDateTime(),                           // Set last visit date to minimum date
            'transactionId'  => $transactionId
        );
        $this->_db->insert($query, $inserts);
        
        // Create cross reference entry to link user to parent
        $this->_createEntityXrefRecord($parentId, $id, $transactionId);
        
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
     * @param array   $values
     * @param boolean $verbose
     * @return \Tranquility\Response
     */
    public function updateUser($values, $verbose) {
        $this->_log->debug('Start of UsersMapper::updateUser() method', $values);
        
        // Validate input fields (audit trail, mandatory fields, and value checks)
        $response = $this->validateInputFields($values);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // One or more fields falied validation - return immediately
            return $response;
        } 
        // End service input validation
        
        // If a password has been provided, hash it now
        $id = Utility::extractValue($values, 'id', 0);
        $password = Utility::extractValue($values, 'password', '');
        if ($password != '') {
            $password = Utility::makeHash($password);
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
        $query .= "SET users.username = :username, users.timezone = :timezone, users.locale = :locale, users.active = :active, users.aclGroup = :aclGroup, users.transactionId = :transactionId \n";
        $updates = array(
            'username'      => Utility::extractValue($values, 'username', ''),
            'timezone'      => Utility::extractValue($values, 'timezone', ''),
            'locale'        => Utility::extractValue($values, 'locale', ''),
            'active'        => Utility::extractValue($values, 'active', 0),
            'aclGroup'      => Utility::extractValue($values, 'securityRole', 0),
            'transactionId' => $transactionId
        );
        
        // If password is being updated, add it to the SQL statement now
        if ($password != '') {
            $query .= ", users.password = :password \n";
            $updates['password'] = $password;
        }
        
        // Complete query
        $query .= "WHERE id = :id ";
        $updates['id'] = $id;

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
     * @param array   $values
     * @param boolean $verbose
     * @return \Tranquility\Response
     */
    public function deleteUser($values, $verbose) {
        $this->_log->debug('Start of UserMapper::deleteUser() method');
        
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
        $query .= "SET users.transactionId = :transactionId \n";
        $query .= "WHERE users.id = :id";
        $values = array(
            'transactionId' => $transactionId,
            'id'            => $id
        );
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
     * Validate a username and password
     * 
     * @param string $username  Username
     * @param string $password  Plaintext password
     * @return boolean
     */
    public function validateUserCredentials($username, $password) {
        $password = Utility::makeHash($password);
        
        $query  = "SELECT entity.*, users.* \n";
        $query .= "  FROM tql_entity AS entity, \n";
        $query .= "       tql_entity_users AS users \n";
        $query .= " WHERE entity.id = users.id \n";
        $query .= "   AND entity.deleted = 0 \n";
        $query .= "   AND entity.type = :entityType \n";
        $query .= "   AND users.username = :username \n";
        $query .= "   AND users.password = :password";
        $params = array(
            'entityType' => EnumEntityType::User,
            'username' => $username,
            'password' => $password
        );
        
        $result = $this->_db->selectOne($query, $params);
        if ($result === null) {
            return false;
        }
        return true;
    }
    
    /**
     * Helper method that performs actual retrieval of user records
     * 
     * @param int    $resultsPerPage
     * @param int    $start
     * @param array  $filters
     * @param string $order
     * @return boolean Array if successful, false on error
     */
    protected function _getUsers($resultsPerPage, $start, $filters = array(), $order = null) {
        // Construct SQL statement to retrieve list of people
        $query  = "SELECT entity.*, users.*, trans.* \n";
        $query .= "  FROM tql_entity AS entity, \n";
        $query .= "       tql_entity_users AS users, \n";
        $query .= "       tql_sys_trans_audit AS trans \n";
        $query .= " WHERE entity.id = users.id \n";
        $query .= "   AND users.transactionId = trans.transactionId \n";
        $query .= "   AND entity.type = :userEntityType \n";
        $query .= "   AND entity.deleted = 0 \n";
        $params = array('userEntityType' => EnumEntityType::User);
        
        // If filters are present, add them to the query
        foreach ($filters as $filterType => $filterValue) {
            switch ($filterType) {
                case 'search':
                    // Text search - try partial string match on username
                    $query .= "  AND users.username LIKE :username \n";
                    $params['username'] = '%'.$filterValue.'%';
                    break;
                case 'username':
                    // Exact match for username only
                    $query .= "  AND users.username = :username \n";
                    $params['username'] = $filterValue;
                    break;
                case 'id':
                    // Filter to a specific person
                    $query .= "  AND users.id = :id \n";
                    $params['id'] = $filterValue;
                    break;
            }
        }
        
        // Add ordering if specified
        if ($order) {
            $query .= "ORDER BY ".$order." \n";
        }
        
        // Limit query
        $query .= $this->buildQueryLimitClause($resultsPerPage, $start);
        
        // Prepare and execute query
        $results = $this->_db->select($query, $params);
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
        $query .= "WHERE a.id = b.id \n";
        $query .= "AND a.id = :id";
        return $this->_db->insert($query, array('id' => $id));
    }
    
    /**
     * Validate all inputs for creating or updating a user record
     * 
     * @param array   $inputs
     * @param boolean $newRecord
     * @param \Tranquility\Response
     */
    public function validateInputFields($inputs, $newRecord = false, $validateAuditTrail = true) {
        // Validate audit trail and mandatory fields
        $response = parent::validateInputFields($inputs, $newRecord, $validateAuditTrail);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // If a mandatory field has not been provided, there is no point going further
            return $response;
        }
        
        // Validate timezone
        $refDataMapper = $this->_getReferenceDataMapper();
        $timezone = Utility::extractValue($inputs, 'timezone', '');
        if (!$refDataMapper->isValidCode($timezone, 'tql_cd_timezones', 'timezone')) {
            $response->addMessage(10006, 'message_10006_invalid_timezone_identifier: '.$timezone, EnumMessageLevel::Error, 'timezone');
        }
        
        // Validate locale
        $locale = Utility::extractValue($inputs, 'locale', '');
        if (!$refDataMapper->isValidCode($locale, 'tql_cd_locales', 'locale')) {
            $response->addMessage(10007, 'message_10007_invalid_locale_identifier: '.$locale, EnumMessageLevel::Error, 'locale');
        }
        
        // Validate security role
        $securityRoleMapper = $this->_getSecurityRoleMapper();
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
                $response->addMessage(10005, 'message_10005_unable_to_locate_specified_parent_entity', EnumMessageLevel::Error, 'parentId');
            }

            // Check if username has already been used for another user account
            $username = Utility::extractValue($inputs, 'username', '');
            $result = $this->getUserByUsername($username);
            if ($result->getItemCount() > 0) {
                // Username is already in use
                $response->addMessage(20203, 'message_20203_username_already_in_use', EnumMessageLevel::Error, 'username');
            }
            
            // Make sure parent does not already have a user account
            $result = $this->getUserByParentId($parentId);
            if ($result->getItemCount() > 0) {
                // Parent already has a user account
                $response->addMessage(20209, 'message_20209_entity_already_has_user_record', EnumMessageLevel::Error, 'parentId');
            }
        } else {
            // We are updating - make sure user already exists
            $id = Utility::extractValue($inputs, 'id', 0);
            if (!$this->_checkEntityExists($id, EnumEntityType::User)) {
                // Supplied ID is not a user, does not exist, or has been marked as deleted
                $response->addMessage(10003, 'message_10003_specified_entity_does_not_exist', EnumMessageLevel::Error, 'id');
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
