<?php

/**
 * Physical address model
 *
 * @uses BusinessObjectMapper
 * @package API
 * @author Andrew Patterson <patto@live.com.au>
 */

use \Tranquility\Utility as Utility;
use \Tranquility\Response as Response;
use \Tranquility\Geolocation as Geolocation;

use \Tranquility\Enum\System\EntityType as EnumEntityType;
use \Tranquility\Enum\System\MessageLevel as EnumMessageLevel;
use \Tranquility\Enum\System\HttpStatusCode as EnumStatusCodes;

use \Tranquility\Enum\Address\Type as EnumAddressType;
use \Tranquility\Enum\Address\SubtypePhysical as EnumPhysicalAddressType;
use \Tranquility\Enum\System\ExternalServiceResponseType as EnumServiceResponseType;

class AddressPhysicalMapper extends AddressMapper {
    
    /**
     * Default mapping for column names to API field names
     * 
     * @return array Keys are API fields, and values
     */
    public function getDefaultFields() {
        $fields = array(
            'id' => 'id',
            'addressType' => 'addressType',
            'addressLine1' => 'addressLine1',
            'addressLine2' => 'addressLine2',
            'city' => 'city',
            'state' => 'state',
            'postcode' => 'postcode',
            'country' => 'country',
            'latitude' => 'latitude',
            'longitude' => 'longitude'
        );
        return $fields;
    }
    
    /**
     * Full list of field mappings for verbose calls
     * Contains all fields in the default 
     * @return array
     */
    public function getVerboseFields() {
        $fields = $this->getDefaultFields();
        
        // Add audit trail fields
        $audit = array(
            'version' => 'version',
            'auditTransactionId' => 'transactionId',
            'auditTransactionSource' => 'transactionSource',
            'auditUpdateBy' => 'updateBy',
            'auditUpdateDatetime' => 'updateDatetime',
            'auditUpdateReason' => 'updateReason'
        );

        $fields = array_merge($fields, $audit);
        return $fields;
    }
    
    /**
     * Returns field IDs for mandatory fields required to create / update address 
     * objects
     * 
     * @return array
     */
    public function getMandatoryFields($newRecord = false) {
        $fields = array(
            'addressType' => 'addressType',
            'addressLine1' => 'addressLine1',
            'city' => 'city',
            'state' => 'state',
            'postcode' => 'postcode',
            'country' => 'country'
        );
        
        // Parent ID is mandatory when creating a new address
        if ($newRecord) {
            $fields['parentId'] = 'parentId';
        }
        
        return $fields;
    }
    
    /**
     * Retrieves a list of physical addresses associated with the specified parent ID
     * 
     * @param int     $parentId
     * @param int     $resultsPerPage
     * @param int     $start
     * @param boolean $verbose
     * @param array   $filter
     * @param array   $addressTypes
     * @return \Tranquility\Response
     */
    public function getAddressList($parentId, $resultsPerPage, $start, $verbose = false, $filter = null) {
        $results = $this->_getAddresses($parentId, EnumAddressType::Physical, $resultsPerPage, $start, $filter);
        $addressList = $this->transformResults($results, $verbose);
        
        // Set up response
        $response = new Response();
        $response->setResponseCode(EnumStatusCodes::OK);
        $response->setContent($addressList['addresses']);
        if (count($addressList) > 0) {
            $response->addMessage(20100, 'message_20100_address_list_retrieved_successfully', EnumMessageLevel::Info);
        } else {
            $response->addMessage(10000, 'message_10000_no_records_returned', EnumMessageLevel::Warning);
        }
        
        // Return response
        return $response;
    }
    
    /**
     * Creates a new physical address record
     * 
     * @param array $values
     * @param boolean $verbose
     * @return \Tranquility\Response
     */
    public function createAddress($values, $verbose) {
        $this->_log->debug('Start of AddressesPhysicalMapper::createAddress() method', $values);
        
        // Validate input fields (audit trail, mandatory fields, and value checks)
        $response = $this->validateInputFields($values, true);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // One or more fields falied validation - return immediately
            return $response;
        } 
        
        // If enabled, attempt geolocation
        $geolocationEnabled = Utility::extractValue($this->_config['geolocation'], 'enabled', false);
        if ($geolocationEnabled) {
            $this->_log->debug('Geolocation is enabled - proceeding with external service call...');
            $geoResult = Geolocation::performGeolocation($values);
            if ($geoResult['status'] != EnumServiceResponseType::Success) {
                $this->_log->error('Error code returned from external geolocation service: '.$geoResult['status']);
            } else {
                $this->_log->info('Geolocation completed successfully.');
            }
            
            $values['latitude'] = Utility::extractValue($geoResult, 'latitude', 0);
            $values['longitude'] = Utility::extractValue($geoResult, 'longitude', 0);
        }
        
        // Start new transaction
        $this->_db->beginTransaction();
        try {
            // Create new transaction record
            $transactionId = $this->_createTransactionRecord($values);

            // Create new entity record
            $parentId = Utility::extractValue($values, 'parentId', 0);
            $id = $this->_createEntityRecord(EnumEntityType::Address, EnumAddressType::Physical);

            // Add address data to table
            $query  = "INSERT INTO tql_entity_addresses_physical (id, addressType, addressLine1, addressLine2, city, state, postcode, country, latitude, longitude, transactionId) \n";
            $query .= "VALUES (:id, :addressType, :addressLine1, :addressLine2, :city, :state, :postcode, :country, :latitude, :longitude, :transactionId)";
            $inserts = array(
                'id'            => $id,
                'addressType'   => Utility::extractValue($values, 'addressType', ''),
                'addressLine1'  => Utility::extractValue($values, 'addressLine1', ''),
                'addressLine2'  => Utility::extractValue($values, 'addressLine2', ''),
                'city'          => Utility::extractValue($values, 'city', ''),
                'state'         => Utility::extractValue($values, 'state', ''),
                'postcode'      => Utility::extractValue($values, 'postcode', ''),
                'country'       => Utility::extractValue($values, 'country', ''),
                'latitude'      => Utility::extractValue($values, 'latitude', 0),
                'longitude'     => Utility::extractValue($values, 'longitude', 0),
                'transactionId' => $transactionId
            );
            $this->_db->insert($query, $inserts);

            // Create XREF record to link the address to its parent
            $query  = "INSERT INTO tql_entity_xref (parentId, childId, transactionId) \n";
            $query .= "VALUES (:parentId, :childId, :transactionId)";
            $inserts = array(
                'parentId'      => $parentId,
                'childId'       => $id,
                'transactionId' => $transactionId
            );
            $result = $this->_db->insert($query, $inserts);
            if ($result == false) {
                $this->_db->rollback();
                $response->setResponseCode(EnumStatusCodes::InternalServerError);
                $response->addMessage(20105, 'message_20105_unable_to_update_address_record', EnumMessageLevel::Error);
                return $response;
            }
        } catch (Exception $ex) {
            $this->_db->rollback();
            throw $ex;
        }
        
        // Commit transaction
        $this->_db->commit();
        
        // Retrieve the address
        $response = $this->getAddressById($parentId, $id, EnumAddressType::Physical, $verbose);
        $response->clearMessages();
        $response->addMessage(20102, 'message_20102_new_address_created_successfully', EnumMessageLevel::Success);
        $response->addTransactionId($transactionId);
        return $response;
    }
    
    /**
     * Updates an existing physical address record
     * 
     * @param type $values
     * @param type $verbose
     * @return \Tranquility\Response
     */
    public function updateAddress($values, $verbose) {
        $this->_log->debug('Start of AddressesPhysicalMapper::updateAddress() method', $values);
        
        // Validate input fields (audit trail, mandatory fields, and value checks)
        $response = $this->validateInputFields($values, false);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // One or more fields falied validation - return immediately
            return $response;
        } 
        
        // If enabled, attempt geolocation
        $geolocationEnabled = Utility::extractValue($this->_config['geolocation'], 'enabled', false);
        if ($geolocationEnabled) {
            $this->_log->debug('Geolocation is enabled - proceeding with external service call...');
            $geoResult = Geolocation::performGeolocation($values);
            if ($geoResult['status'] != EnumServiceResponseType::Success) {
                $this->_log->error('Error code returned from external geolocation service: '.$geoResult['status']);
            } else {
                $this->_log->info('Geolocation completed successfully.');
            }
            
            $values['latitude'] = Utility::extractValue($geoResult, 'latitude', 0);
            $values['longitude'] = Utility::extractValue($geoResult, 'longitude', 0);
        }
        
        // Start new transaction
        $this->_db->beginTransaction();
        try {
            // Create new transaction record
            $id = Utility::extractValue($values, 'id', 0, 'int');
            $parentId = Utility::extractValue($values, 'parentId');
            $transactionId = $this->_createTransactionRecord($values);

            // Create historical record
            $result = $this->_createHistoricalPhysicalAddressRecord($id);
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
                $response->addMessage(20105, 'message_20105_unable_to_update_address_record', EnumMessageLevel::Error);
                return $response;
            }

            // Update main record with new details
            $query  = "UPDATE tql_entity_addresses_physical \n";
            $query .= "SET addressType = :addressType, \n";
            $query .= "    addressLine1 = :addressLine1, \n";
            $query .= "    addressLine2 = :addressLine2, \n";
            $query .= "    city = :city, \n";
            $query .= "    state = :state, \n";
            $query .= "    postcode = :postcode, \n";
            $query .= "    country = :country, \n";
            $query .= "    latitude = :latitude, \n";
            $query .= "    longitude = :longitude, \n";
            $query .= "    transactionId = :transactionId \n";
            $query .= "WHERE id = :id";
            $updates = array(
                'addressType' => Utility::extractValue($values, 'addressType'),
                'addressLine1' => Utility::extractValue($values, 'addressLine1'),
                'addressLine2' => Utility::extractValue($values, 'addressLine2'),
                'city' => Utility::extractValue($values, 'city'),
                'state' => Utility::extractValue($values, 'state'),
                'postcode' => Utility::extractValue($values, 'postcode'),
                'country' => Utility::extractValue($values, 'country'),
                'latitude' => Utility::extractValue($values, 'latitude'),
                'longitude' => Utility::extractValue($values, 'longitude'),
                'transactionId' => $transactionId,
                'id' => $id
            );
            $result = $this->_db->update($query, $updates);
            if ($result == false) {
                $this->_db->rollback();
                $response->setResponseCode(EnumStatusCodes::InternalServerError);
                $response->addMessage(20105, 'message_20105_unable_to_update_address_record', EnumMessageLevel::Error);
                return $response;
            }
        } catch (Exception $ex) {
            $this->_db->rollback();
            throw $ex;
        }
        
        // Commit transaction
        $this->_db->commit();
        
        // Build success response
        $response = $this->getAddressById($parentId, $id, EnumAddressType::Physical, $verbose);
        $response->clearMessages();
        $response->setResponseCode(EnumStatusCodes::OK);
        $response->addMessage(20106, 'message_20106_address_updated_successfully', EnumMessageLevel::Success);
        $response->addTransactionId($transactionId);
        return $response;
    }
    
    /**
     * Logically deletes the physical address record for the supplied ID
     * 
     * @param array $values
     * @param boolean $verbose
     * @return \Tranquility\Response
     */
    public function deleteAddress($values, $verbose) {
        $this->_log->debug('Start of AddressesPhysicalMapper::updateAddress() method', $values);
        
        // Validate audit trail fields only 
        $response = $this->_validateAuditTrailValues($values);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // One or more fields falied validation - return immediately
            return $response;
        } 
        
        // Check address entity exists
        $id = Utility::extractValue($values, 'id', 0);
        if (!$this->_checkEntityExists($id, 'address')) {
            // Supplied ID is not a address, does not exist, or is already deleted
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
        $result = $this->_createHistoricalPhysicalAddressRecord($id);
        if ($result == false) {
            $this->_db->rollback();
            $response->setResponseCode(EnumStatusCodes::InternalServerError);
            $response->addMessage(10004, 'message_10004_unable_to_create_historical_entity_record', EnumMessageLevel::Error);
            return $response;
        }
        
        // Mark record as logically deleted and increment version number
        $this->_deleteEntityRecord($id);
        
        // Update current record with audit trail details
        $query  = "UPDATE tql_entity_addresses_physical \n";
        $query .= "SET transactionId = :transactionId \n";
        $query .= "WHERE id = :id";
        $values = array(
            'transactionId' => $transactionId, 
            'id' => $id
        );
        $result = $this->_db->update($query, $values);
        if ($result == false) {
            $this->_db->rollback();
            $response->setResponseCode(EnumStatusCodes::InternalServerError);
            $response->addMessage(20107, 'message_20107_unable_to_delete_address_record', EnumMessageLevel::Error);
            return $response;
        }
        
        // Commit transaction
        $this->_db->commit();
        
        // Build success response
        $response->clearMessages();
        $response->setResponseCode(EnumStatusCodes::OK);
        $response->addMessage(20104, 'message_20104_address_deleted_successfully', EnumMessageLevel::Success);
        $response->addTransactionId($transactionId);
        return $response;
    }
    
    /**
     * Helper method that copies the current address record into a history table
     * @param int $id 
     * @return boolean
     */
    protected function _createHistoricalPhysicalAddressRecord($id) {
        // Copy existing record into historical table
        $query  = "INSERT INTO tql_history_addresses_physical (id, version, addressType, addressLine1, addressLine2, city, state, postcode, country, latitude, longitude, transactionId) \n";
        $query .= "SELECT a.id, a.version, b.addressType, b.addressLine1, b.addressLine2, b.city, b.state, b.postcode, b.country, b.latitude, b.longitude, b.transactionId \n";
        $query .= "FROM tql_entity AS a, \n";
        $query .= "     tql_entity_addresses_physical AS b \n";
        $query .= "WHERE a.id = ? \n";
        $query .= "AND a.id = b.id ";
        return $this->_db->insert($query, array($id));
    }
    
    /**
     * Validate all inputs for creating or updating a physical address record
     * 
     * @param array   $inputs
     * @param boolean $newRecord
     * @param boolean $validateAuditTrail
     */
    public function validateInputFields($inputs, $newRecord = false, $validateAuditTrail = true) {
        $response = parent::validateInputFields($inputs, $newRecord, $validateAuditTrail);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // If a mandatory field has not been provided, there is no point going further
            return $response;
        }
        
        // Check that parent entity exists
        $parentId = Utility::extractValue($inputs, 'parentId', 0);
        if (!$this->_checkEntityExists($parentId)) {
            $response->addMessage(10005, 'message_10005_unable_to_locate_specified_parent_entity', EnumMessageLevel::Error, 'parentId');
        }
        
        // Validate address type
        $addressType = Utility::extractValue($inputs, 'addressType', '');
        if (!EnumPhysicalAddressType::isValidValue($addressType)) {
            $response->addMessage(20103, 'message_20103_invalid_address_type', EnumMessageLevel::Error, 'addressType');
        }

        // If any validation errors occurred, set the response type to an error
        if ($response->getMessageCount() > 0) {
            $response->setResponseCode(EnumStatusCodes::BadRequest);
        }
        
        return $response;
    }
    
    
}
