<?php

/**
 * Base class for data mapping / model classes
 *
 * @uses BusinessObjectMapper
 * @package API
 * @author Andrew Patterson <patto@live.com.au>
 */

use \Tranquility\Utility as Utility;
use \Tranquility\Enum\System\TransactionSource as EnumTransactionSource;
use \Tranquility\Enum\System\MessageLevel as EnumMessageLevel;
use \Tranquility\Enum\System\HttpStatusCode as EnumStatusCodes;
use \Tranquility\Enum\System\EntityType as EnumEntityType;
use \Tranquility\Response as Response;

class BusinessObjectMapper {
    
    /**
     * Database connection
     * @var \Tranquility\Database
     */
    protected $_db;
    
    /**
     * Logging class
     * @var Monolog\Logger
     */
    protected $_log;
    
    /**
     * Configuration array
     * @var array
     */
    protected $_config;
    
    /**
     * Data mapper for reference data tables
     * @var ReferenceDataMapper
     */
    protected $_referenceDataMapper;
    
    /**
     * Data mapper for security roles
     * @var SecurityRoleMapper
     */
    protected $_securityRoleMapper;
    
    /**
     * Constructor
     * 
     * @param array                 $config Configuration parameters
     * @param \Tranquility\Database $db     Database connection
     * @param \Monolog\Logger       $log    Logging class
     */
    public function __construct($config, $db, $log) {
        $this->_db = $db;
        $this->_log = $log;
        $this->_config = $config;
    }
    
    /**
     * Returns the list of fields that will be displayed for a business object.
     * Designed to be extended by a specific entity mapper class.
     * 
     * @return array
     */
    public function getDefaultFields() {
        return array();
    }
    
    /**
     * Returns the list of fields that will be displayed for a business object
     * if the API is called with verbosity turned on. Designed to be extended
     * by a specific entity mapper class.
     * 
     * @return array
     */
    public function getVerboseFields() {
        return array();
    }
    
    /**
     * Returns the list of mandatory fields required when creating or updating
     * a business object. Designed to be extended by a specific entity mapper
     * class.
     * 
     * @param boolean $newRecord
     * @return array
     */
    public function getMandatoryFields($newRecord = false) {
        return array();
    }
    
    /**
     * Returns the list of fields that make up the audit trail for a business
     * object.
     * 
     * @return array
     */
    public function getAuditTrailFields() {
        return array(
            'updateBy',
            'updateReason',
            'updateDatetime',
            'transactionSource'
        );
    }
    
    /**
     * Formats an array of business object data. Depending on verbosity, either
     * the default or verbose set of fields will be used. A custom set of fields
     * can also be supplied
     * 
     * @param array $results
     * @param array $verbose
     * @param array $customFields
     * @return array
     */
    public function transformResults($results, $verbose, $customFields = array()) {
        if (is_array($customFields) && count($customFields) > 0) {
            $fields = $customFields;
        } else if ($verbose) {
            $fields = $this->getVerboseFields();
        } else {
            $fields = $this->getDefaultFields();
        }

        // Only include value if it appears in the defined list of fields
        $transformedResults = array();
        foreach ($results as $row) {
            $entry = array();
            foreach ($fields as $key => $value) {
                if (property_exists($row, $value)) {
                    $entry[$key] = $row->$value;
                } else {
                    $entry[$key] = null;
                }
            }
            $transformedResults[] = $entry;
        }
        
        return $transformedResults;
    }
    
    /**
     * Generates the LIMIT clause for an SQL statement. If number of results
     * per page is supplied as zero, no limit will be applied
     * 
     * @param int $resultsPerPage
     * @param int $start
     * @return string
     */
    protected function buildQueryLimitClause($resultsPerPage, $start) {
        $resultsPerPage = (int)$resultsPerPage;
        $start = (int)$start;
        
        if ($resultsPerPage == 0) {
            // If no limit is specified, return everything
            $limit = '';
        } else {
            $limit = "LIMIT ".$start.", ".$resultsPerPage;
        }
        return $limit;
    }
    
    /**
     * Performs validation on mandatory audit trail fields:
     *   - updateBy
     *   - updateReason
     *   - updateDatetime
     *   - transactionSource
     * 
     * @param  array $inputs 
     * @return Tranquility\Response
     */
    protected function _validateAuditTrailValues($inputs) {
        $response = new Response();
        
        // Perform mandatory audit trail field validation
        $mandatoryFields = $this->getAuditTrailFields();
        foreach ($mandatoryFields as $field) {
            if (!isset($inputs[$field]) || $inputs[$field] == null || $inputs[$field] == '') {
                // Mandatory field missing
                $response->setResponseCode(EnumStatusCodes::BadRequest);
                $response->addMessage(10001, 'message_10001_mandatory_audit_trail_information_missing', EnumMessageLevel::Error, $field);
            }
        }
        
        // If a mandatory field has not been provided, there is no point going further
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            return $response;
        }
        
        // Check that 'updateBy' is a valid person / user
        $updateBy = Utility::extractValue($inputs, 'updateBy', 0, 'int');
        /*if (!$this->_checkEntityExists($updateBy, 'user')) {
            $response->addMessage(10003, 'message_10003_specified_entity_does_not_exist', EnumMessageLevel::Error, 'updateBy');
        }*/
        
        // Check that 'updateDatetime' has been supplied in a valid MySQL datetime format 
        if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $inputs['updateDatetime']) !== 1) {
            $response->addMessage(10008, 'message_10008_invalid_datetime_format', EnumMessageLevel::Error, 'updateDatetime');
        }
        
        // Check that 'transactionSource' is a known source 
        if (!EnumTransactionSource::isValidValue($inputs['transactionSource'])) {
            // Transaction source not supplied, or is invalid
            $response->addMessage(10009, 'message_10009_invalid_transaction_source_code', EnumMessageLevel::Error, 'transactionSource');
        }
        
        // If one or more error messages were added, set the BadRequest response code
        if ($response->getMessageCount() > 0) {
            $response->setResponseCode(EnumStatusCodes::BadRequest);
        }
        
        return $response;
    }
    
    /**
     * Performs generic validation for entities. Should be extended by specific
     * entity mapper classes
     * 
     * @param array   $inputs
     * @param boolean $newRecord
     * @param boolean $validateAuditTrail
     * @return Tranquility\Response
     */
    public function validateInputFields($inputs, $newRecord = false, $validateAuditTrail = true) {
        $response = new Response();
        
        // If flag is set, validate audit trail inputs first
        if ($validateAuditTrail) {
            $response = $this->_validateAuditTrailValues($inputs);
        }
        
        // Perform mandatory field validation
        $mandatoryFields = $this->getMandatoryFields($newRecord);
        foreach ($mandatoryFields as $field) {
            if (!isset($inputs[$field]) || $inputs[$field] == null || $inputs[$field] == '') {
                // Mandatory field missing
                $response->setResponseCode(EnumStatusCodes::BadRequest);
                $response->addMessage(10002, 'message_10002_mandatory_service_input_field_missing', EnumMessageLevel::Error, $field);
            }
        }
        
        // Return the response
        return $response;
    }
    
    /**
     * Creates a new entity record
     * 
     * @param string $type The entity type to create
     * @return int The auto-generated ID for the entity
     */
    protected function _createEntityRecord($type, $subType = null) {
        // Validate entity type passed in once this has been finalised
        if (!EnumEntityType::isValidValue($type)) {
            throw new \Tranquility\Exception('Unknown entity type supplied when trying to create new entity: '.$type);
        }
        
        // Create new entity record
        $query  = "INSERT INTO tql_entity (type, subType, version, deleted, locked, lockedBy, lockedDatetime) \n";
        $query .= "VALUES (:type, :subType, :version, :deleted, :locked, :lockedBy, :lockedDatetime)";
        $values = array(
            'type'           => $type,                         // Entity type
            'subType'        => $subType,                      // Entity sub-type
            'version'        => 1,                             // Version
            'deleted'        => 0,                             // Deleted flag
            'locked'         => 0,                             // Locked flag
            'lockedBy'       => 0,                             // Locked by user ID
            'lockedDatetime' => Utility::getDbMinDateTime()    // Locked timestamp
        );
        $this->_db->insert($query, $values);
        return $this->_getLastInsertId('id');
    }
    
    /**
     * Marks an entity as deleted. The record is NOT actually removed from the
     * database.
     * 
     * @param int $id
     * @return boolean
     */
    protected function _deleteEntityRecord($id) {
        // Mark record as deleted
        $query  = "UPDATE tql_entity \n";
        $query .= "SET deleted = 1, \n";
        $query .= "    version = (version + 1) \n";
        $query .= "WHERE id = :id";
        $values = array('id' => (int)$id);
        $result = $this->_db->update($query, $values);
        if ($result <= 0) {
            return false;
        }
        return true;
    }
    
    /**
     * Removes 'deleted' flag from a record that has previously been marked as deleted
     * 
     * @param int $id
     * @return boolean
     */
    protected function _undeleteEntityRecord($id) {
        // Mark record as active
        $query  = "UPDATE tql_entity \n";
        $query .= "SET deleted = 0, \n";
        $query .= "    version = (version + 1) \n";
        $query .= "WHERE id = :id";
        $values = array('id' => (int)$id);
        $result = $this->_db->update($query, $values);
        if ($result <= 0) {
            return false;
        }
        return true;
    }
    
    /**
     * Updates the version counter for an existing entity by one
     * 
     * @param int $id
     * @return boolean
     */
    protected function _incrementEntityVersion($id) {
        // Update existing record
        $query  = "UPDATE tql_entity \n";
        $query .= "SET version = (version + 1) \n";
        $query .= "WHERE id = :id";
        $values = array('id' => (int)$id);
        $result = $this->_db->update($query, $values);
        if ($result <= 0) {
            return false;
        }
        return true;
    }
    
    /**
     * Checks if an entity record exists for the specified ID.
     * 
     * @param  int      $id              Entity id to check
     * @param  string   $entityType      Optional check that the entity is of the required type
     * @param  boolean  $includeDeleted  Optional - set to true to include logically deleted records in check
     * @return boolean  True if entity exists, otherwise false
     */
    protected function _checkEntityExists($id, $entityType = '', $includeDeleted = false) {
        // Attempt to locate entity record
        $query  = "SELECT entity.* ";
        $query .= "FROM tql_entity AS entity ";
        $query .= "WHERE entity.id = :id ";
        $values = array('id' => (int)$id);
        
        // Add additional filter conditions
        if (!$includeDeleted) {
            $query .= "AND deleted = :deletedStatus ";
            $values['deletedStatus'] = 0;
        }
        if ($entityType !== '') {
            $query .= "  AND entity.type = :entityType";
            $values['entityType'] = $entityType;
        }
        
        // Check for existence of entity record
        $results = $this->_db->select($query, $values);
        if (count($results) > 0) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Returns the details of the specified entity
     * 
     * @param int$id
     * @return array
     */
    protected function _getEntityDetails($id) {
        // Ensure entity ID is an integer
        $id = (int)$id;
        
        // Attempt to locate entity record
        $query  = "SELECT entity.* \n";
        $query .= "FROM tql_entity AS entity \n";
        $query .= "WHERE entity.id = :id";
        $values = array('id' => $id);
        $results = $this->_db->selectOne($query, $values);
        return $results;
    }
    
    /**
     * Creates a new transaction record
     * 
     * The transaction record will contain all of the audit trail information
     * and should be associated with any business object record being updated.
     * 
     * @param array $data Audit trail inputs:
     *                      - transactionSource
     *                      - updateBy
     *                      - updateDatetime
     *                      - updateReason
     * @return int The auto-generated transaction ID
     */
    protected function _createTransactionRecord($data) {
        // Create new entity record
        $query  = "INSERT INTO tql_sys_trans_audit (transactionSource, updateBy, updateDatetime, updateReason) \n";
        $query .= "VALUES (?, ?, ?, ?)";
        $values = array(
            $data['transactionSource'],
            $data['updateBy'],
            $data['updateDatetime'],
            $data['updateReason']
        );
        $this->_db->insert($query, $values);
        $transactionId = $this->_getLastInsertId('transactionId');
        return $transactionId;
    }
    
    protected function _getLastInsertId($idColumnName = null) {
        return $this->_db->lastInsertId($idColumnName);
    }
    
    protected function _createEntityXrefRecord($parentId, $childId, $transactionId) {
        // Check parent entity exists
        $parentId = (int)$parentId;
        if (!$this->_checkEntityExists($parentId)) {
            throw new \Tranquility\Exception('Supplied parent entity does not exist: '.$parentId);
        }
        
        // Check child entity exists
        $childId = (int)$childId;
        if (!$this->_checkEntityExists($childId)) {
            throw new \Tranquility\Exception('Supplied child entity does not exist: '.$childId);
        }
        
        // Create new entity xref record
        $query  = "INSERT INTO tql_entity_xref (parentId, childId, transactionId) \n";
        $query .= "VALUES (:parentId, :childId, :transactionId)";
        $values = array(
            'parentId'      => $parentId,
            'childId'       => $childId,
            'transactionId' => $transactionId
        );
        $result = $this->_db->insert($query, $values);
        return $result;
    }
    
    public function getRelatedEntityTypes($entityId, $relationship, $entityType = null, $entitySubType = null) {
        // Validate inputs
        $entityId = (int)$entityId;
        if (is_string($entityType)) {
            $entityType = array($entityType);
        }
        if (is_string($entitySubType)) {
            $entitySubType = array($entitySubType);
        }
        
        // Validate relationship
        $relationshipTypes = array('parent', 'child');
        $relationship = strtolower($relationship);
        if (!in_array($relationship, $relationshipTypes)) {
            throw new \Tranquility\Exception('Entity relationship type must be "parent" or "child" - supplied relationship was "'.$relationship.'"');
        }
        
        // Retrieve the distinct set of entity types related to the supplied entity
        $query  = "SELECT child_entity.type, child_entity.subType \n";
        $query .= "  FROM tql_entity AS parent_entity, \n";
        $query .= "       tql_entity AS child_entity, \n";
        $query .= "       tql_entity_xref AS xref \n";
        $query .= " WHERE xref.parentId = parent_entity.id \n";
        $query .= "   AND xref.childId = child_entity.id \n";
        $query .= "   AND parent_entity.deleted = 0 \n";
        $query .= "   AND child_entity.deleted = 0 \n";
        
        // Addresses and user accounts are not independent entities - they should be loaded as properties of an independent entity (i.e. a person)
        $query .= "   AND child_entity.type NOT IN ('".EnumEntityType::Address."', '".EnumEntityType::User."') \n";   

        // Final selection depends on whether we are working with parent or child
        if ($relationship == "child") {
            $query .= "   AND parent_entity.id = ? \n";
            $values = array($entityId);

            // Filter on entity type if required
            if (is_array($entityType) && count($entityType) > 0) {
                $params = implode(', ', array_fill(0, count($entityType), '?'));
                $query .= "AND child_entity.type IN (".$params.") \n";
                $values = array_merge($values, $entityType);
            }

            // Filter on entity sub-type if required
            if (is_array($entitySubType) && count($entitySubType) > 0) {
                $params = implode(', ', array_fill(0, count($entitySubType), '?'));
                $query .= "AND child_entity.subType = IN (".$params.") \n";
                $values = array_merge($values, $entitySubType);
            }
        } else {
            $query .= "   AND child_entity.id = ? \n";
            $values = array($entityId);
        }

        
        // Execute query and return results (false if no results)
        $results = $this->_db->select($query, $values);
        if (count($results) <= 0) {
            return false;
        }
        
        return $results;
    }
    
    protected function _getReferenceDataMapper() {
        if ($this->_referenceDataMapper == null) {
            $this->_referenceDataMapper = new ReferenceDataMapper($this->_config, $this->_db, $this->_log);
        }
        return $this->_referenceDataMapper;
    }
    
    protected function _getSecurityRoleMapper() {
        if ($this->_securityRoleMapper == null) {
            $this->_securityRoleMapper = new SecurityRoleMapper($this->_config, $this->_db, $this->_log);
        }
        return $this->_securityRoleMapper;
    }
}
