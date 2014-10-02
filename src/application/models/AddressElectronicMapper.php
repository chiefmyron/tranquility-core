<?php

/**
 * Electronic address model
 *
 * @uses BusinessObjectMapper
 * @package API
 * @author Andrew Patterson <patto@live.com.au>
 */

use \Tranquility\Utility as Utility;
use \Tranquility\Response as Response;

use \Tranquility\Enum\System\EntityType as EnumEntityType;
use \Tranquility\Enum\System\MessageLevel as EnumMessageLevel;
use \Tranquility\Enum\System\HttpStatusCode as EnumStatusCodes;

use \Tranquility\Enum\Address\Type as EnumAddressType;
use \Tranquility\Enum\Address\SubtypeElectronic as EnumElectronicAddressType;


class AddressElectronicMapper extends AddressMapper {
    
    /**
     * Default mapping for column names to API field names
     * 
     * @return array Keys are API fields, and values
     */
    public function getDefaultFields() {
        $fields = array(
            'id' => 'id',
            'addressType' => 'addressType',
            'category' => 'category',
            'addressText' => 'addressText',
            'primaryContact' => 'primaryContact'
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
     * Returns field IDs for mandatory fields required to create / update People 
     * objects
     * 
     * @return array
     */
    public function getMandatoryFields($newRecord = false) {
        $fields = array(
            'addressType' => 'addressType',
            'category' => 'category',
            'addressText' => 'addressText'
        );
        
        // Parent ID is mandatory when creating a new address
        if ($newRecord) {
            $fields['parentId'] = 'parentId';
        }
        
        return $fields;
    }
    
    /**
     * Retrieves a list of electronic addresses associated with the specified parent ID
     * 
     * @param int     $parentId
     * @param int     $resultsPerPage
     * @param int     $start
     * @param boolean $verbose
     * @param array   $filter
     * @param array   $addressTypes
     * @return \Tranquility\Response
     */
    public function getAddressList($parentId, $resultsPerPage, $start, $verbose = false, $filter = null, $addressTypes = array()) {
        $results = $this->_getAddresses($parentId, EnumAddressType::Electronic, $resultsPerPage, $start, $filter);
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
     * Creates a new electronic address record
     * 
     * @param array   $values
     * @param boolean $verbose
     * @return \Tranquility\Response
     */
    public function createAddress($values, $verbose = false) {
        $this->_log->debug('Start of AddressesElectronicMapper::createAddress() method', $values);
        
        // Validate input fields (audit trail, mandatory fields, and value checks)
        $response = $this->validateInputFields($values, true);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // One or more fields falied validation - return immediately
            return $response;
        } 
        
        // Start new transaction
        $this->_db->beginTransaction();
        try {
            // Create new transaction record
            $transactionId = $this->_createTransactionRecord($values);

            // Create new entity record
            $id = $this->_createEntityRecord(EnumEntityType::Address, EnumAddressType::Electronic);
            $parentId = Utility::extractValue($values, 'parentId', 0);
            $primaryContact = Utility::extractValue($values, 'primaryContact', 0);
            
            // If this is the new primary contact, remove flag from previou primary contact
            if ($primaryContact == 1) {
                $this->_log->debug('Changing primary contact flag for electronic address');
                $result = $this->_removePrimaryContactFlag($parentId, $transactionId);
                if ($result == false) {
                    $this->_db->rollback();
                    $response->setResponseCode(EnumStatusCodes::InternalServerError);
                    $response->addMessage(20105, 'message_20105_unable_to_update_address_record', EnumMessageLevel::Error);
                    return $response;
                }
            }

            // Add address data to table
            $query  = "INSERT INTO tql_entity_addresses_electronic (id, addressType, category, addressText, primaryContact, transactionId) \n";
            $query .= "VALUES (:id, :addressType, :category, :addressText, :primaryContact, :transactionId)";
            $inserts = array(
                'id'             => $id,
                'addressType'    => Utility::extractValue($values, 'addressType', ''),
                'category'       => Utility::extractValue($values, 'category', ''),
                'addressText'    => Utility::extractValue($values, 'addressText', ''),
                'primaryContact' => Utility::extractValue($values, 'primaryContact', 0),
                'transactionId'  => $transactionId
            );
            $this->_db->insert($query, $inserts);

            // Create XREF record to link the address to its parent
            $result = $this->_createEntityXrefRecord($parentId, $id, $transactionId);
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
        $response = $this->getAddressById($parentId, $id, EnumAddressType::Electronic, $verbose);
        $response->clearMessages();
        $response->addMessage(20102, 'message_20102_new_address_created_successfully', EnumMessageLevel::Success);
        $response->addTransactionId($transactionId);
        return $response;
    }
    
    /**
     * Updates an existing electronic address record
     * 
     * @param array   $values
     * @param boolean $verbose
     * @return \Tranquility\Response
     */
    public function updateAddress($values, $verbose = false) {
        $this->_log->debug('Start of AddressesElectronicMapper::updateAddress() method', $values);
        
        // Validate input fields (audit trail, mandatory fields, and value checks)
        $response = $this->validateInputFields($values, false);
        if ($response->getResponseCode() == EnumStatusCodes::BadRequest) {
            // One or more fields falied validation - return immediately
            return $response;
        } 
        
        // Start new transaction
        $this->_db->beginTransaction();
        try {
            // Create new transaction record
            $id = Utility::extractValue($values, 'id', 0, 'int');
            $parentId = Utility::extractValue($values, 'parentId');
            $primaryContact = Utility::extractValue($values, 'primaryContact', 0);
            $transactionId = $this->_createTransactionRecord($values);
            
            // If this is the new primary contact, remove flag from previou primary contact
            if ($primaryContact == 1) {
                $this->_log->debug('Changing primary contact flag for electronic address');
                $result = $this->_removePrimaryContactFlag($parentId, $transactionId);
                if ($result == false) {
                    $this->_db->rollback();
                    $response->setResponseCode(EnumStatusCodes::InternalServerError);
                    $response->addMessage(20105, 'message_20105_unable_to_update_address_record', EnumMessageLevel::Error);
                    return $response;
                }
            }

            // Create historical record
            $result = $this->_createHistoricalElectronicAddressRecord($id);
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
            $query  = "UPDATE tql_entity_addresses_electronic \n";
            $query .= "SET addressType = :addressType, \n";
            $query .= "    category = :category, \n";
            $query .= "    addressText= :addressText, \n";
            $query .= "    primaryContact = :primaryContact, \n";
            $query .= "    transactionId = :transactionId \n";
            $query .= "WHERE id = :id";
            $updates = array(
                'addressType' => Utility::extractValue($values, 'addressType'),
                'category' => Utility::extractValue($values, 'category'),
                'addressText' => Utility::extractValue($values, 'addressText'),
                'primaryContact' => Utility::extractValue($values, 'primaryContact'),
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
        $response = $this->getAddressById($parentId, $id, EnumAddressType::Electronic, $verbose);
        $response->clearMessages();
        $response->setResponseCode(EnumStatusCodes::OK);
        $response->addMessage(20106, 'message_20106_address_updated_successfully', EnumMessageLevel::Success);
        $response->addTransactionId($transactionId);
        return $response;
    }
    
    /**
     * Logically deletes the electronic address record for the supplied ID
     * 
     * @param array   $values
     * @param boolean $verbose
     * @return \Tranquility\Response
     */
    public function deleteAddress($values, $verbose = false) {
        $this->_log->debug('Start of AddressesElectronicMapper::updateAddress() method', $values);
        
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
        $result = $this->_createHistoricalElectronicAddressRecord($id);
        if ($result == false) {
            $this->_db->rollback();
            $response->setResponseCode(EnumStatusCodes::InternalServerError);
            $response->addMessage(10004, 'message_10004_unable_to_create_historical_entity_record', EnumMessageLevel::Error);
            return $response;
        }
        
        // Mark record as logically deleted and increment version number
        $this->_deleteEntityRecord($id);
        
        // Update current record with audit trail details
        $query  = "UPDATE tql_entity_addresses_electronic \n";
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
    protected function _createHistoricalElectronicAddressRecord($id) {
        // Copy existing record into historical table
        $query  = "INSERT INTO tql_history_addresses_electronic (id, version, addressType, category, addressText, primaryContact, transactionId) \n";
        $query .= "SELECT a.id, a.version, b.addressType, b.category, b.addressText, b.primaryContact, b.transactionId \n";
        $query .= "FROM tql_entity AS a, \n";
        $query .= "     tql_entity_addresses_electronic AS b \n";
        $query .= "WHERE a.id = ? \n";
        $query .= "AND a.id = b.id ";
        return $this->_db->insert($query, array($id));
    }
    
    /**
     * Removes the 'Primary Contact' flag from any existing electronic addresses
     * 
     * @param  int $parentId
     * @param  int $transactionId
     * @return boolean
     */
    protected function _removePrimaryContactFlag($parentId, $transactionId) {
        $filter = array ('primaryContact' => 1);
        $result = $this->_getAddresses($parentId, EnumAddressType::Electronic, 1, 1, $filter);
        if ($result == false) {
            $this->_log->debug('No previous telephone addresses marked as primary contact');
            return true;
        }
        
        // Check for multiple addresses with primary contact
        if (count($result) > 1) {
            $this->_log->error('Multiple electronic addresses flagged as primary contact for parent entity '.$parentId);
        }
        
        // Get address details
        $address = $result[0];
        
        // Create historical record for this address
        $result = $this->_createHistoricalElectronicAddressRecord($address->id);
        if ($result == false) {
            return $result;
        }
        
        // Update the primary contact flag
        $query  = "UPDATE tql_entity_addresses_electronic \n";
        $query .= "SET primaryContact = 0, \n";
        $query .= "    transactionId = :transactionId \n";
        $query .= "WHERE id = :id";
        $updates = array(
            'transactionId' => $transactionId,
            'id' => $address->id
        );
        return $this->_db->update($query, $updates);
    }
    
    /**
     * Validate all inputs for creating or updating a electronic address record
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
        if (!EnumElectronicAddressType::isValidValue($addressType)) {
            $response->addMessage(20103, 'message_20103_invalid_address_type', EnumMessageLevel::Error, 'addressType');
        }
        
        // Address text validation is different depending on address type
        $addressText = Utility::extractValue($inputs, 'addressText', '');
        switch ($addressType) {
            case EnumElectronicAddressType::Email:
                // Regex check: ^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$
                $validAddress = preg_match('^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$^', $addressText);
                break;
            case EnumElectronicAddressType::URL:
                // Regex check: 
                $validAddress = false;
                break;
        }
        if (!$validAddress) {
            $response->addMessage(10010, 'message_10010_invalid_email_address_format', EnumMessageLevel::Error, 'addressText');
        }

        // If any validation errors occurred, set the response type to an error
        if ($response->getMessageCount() > 0) {
            $response->setResponseCode(EnumStatusCodes::BadRequest);
        }
        
        return $response;
    }
}
