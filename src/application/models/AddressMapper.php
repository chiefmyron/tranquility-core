<?php

/**
 * Address model - handles physical, electronic and phone address types
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

class AddressMapper extends BusinessObjectMapper {
    
    /**
     * Retrieves a list of addresses associated with the specified parent ID
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
        $addressList = array();
        
        // If no address types have been specified, retrieve everything
        if (!is_array($addressTypes) || count($addressTypes) == 0) {
            $addressTypes = array(
                EnumAddressType::Physical,
                EnumAddressType::Electronic,
                EnumAddressType::Phone
            );
        }
        
        foreach ($addressTypes as $type) {
            // The mapper to use will depend on the address type
            $mapper = $this->_getAddressMapper($type);
            $result = $mapper->getAddressList($parentId, $resultsPerPage, $start, $verbose, $filter);
            $addressList[$type] = $result->getContent();
        }
        
        // Set up response
        $response = new Response();
        $response->setResponseCode(EnumStatusCodes::OK);
        $response->setContent(array('addresses' => $addressList));
        if (count($addressList) > 0) {
            $response->addMessage(20100, 'message_20100_address_list_retrieved_successfully', EnumMessageLevel::Info);
        } else {
            $response->addMessage(10000, 'message_10000_no_records_returned', EnumMessageLevel::Warning);
        }
        
        // Return response
        return $response;
    }
    
    /**
     * Retrieves a single address record for the parent entity 
     * 
     * @param int    $parentId
     * @param int    $addressId
     * @param string $type
     * @param bool   $verbose
     * @return \Tranquility\Response
     */
    public function getAddressById($parentId, $addressId, $type, $verbose = false) {
        $filter = array();
        $filter[] = array(
            'column' => 'address.id',
            'operator' => '=',
            'value' => $addressId
        );
        
        // Retrieve the address
        $results = $this->_getAddresses($parentId, $type, 1, 0, $filter);
        $addressList = $this->transformResults($results, $verbose);
        
        // Set up response
        $response = new Response();
        $response->setResponseCode(EnumStatusCodes::OK);
        $response->setContent($addressList);
        if (count($results) > 0) {
            $response->addMessage(20101, 'message_20101_single_address_retrieved_successfully', EnumMessageLevel::Info);
        } else {
            $response->addMessage(10000, 'message_10000_no_records_returned', EnumMessageLevel::Warning);
        }
        
        // Return response
        return $response;
    }
    
    /**
     * Creates a new address record
     * 
     * @param array $values
     * @param boolean $verbose
     * @return \Tranquility\Response
     */
    public function createAddress($values, $verbose = false) {
        $type = Utility::extractValue($values, 'type', null);
        $mapper = $this->_getAddressMapper($type);
        $response = $mapper->createAddress($values, $verbose);
        return $response;
    }
    
    /**
     * Updates an existing address record
     * 
     * @param array   $values
     * @param boolean $verbose
     * @return \Tranquility\Response
     */
    public function updateAddress($values, $verbose = false) {
        $type = Utility::extractValue($values, 'type', '');
        if ($type == '') {
            $id = Utility::extractValue($values, 'id', 0);
            $entity = $this->_getEntityDetails($id);
            $type = Utility::extractValue($entity, 'subType', '');
        }
        
        $mapper = $this->_getAddressMapper($type);
        $response = $mapper->updateAddress($values, $verbose);
        return $response;
    }
    
    /**
     * Deletes an existing address record
     * 
     * @param array   $values
     * @param boolean $verbose
     * @return \Tranquility\Response
     */
    public function deleteAddress($values, $verbose = false) {
        $type = Utility::extractValue($values, 'type', '');
        if ($type == '') {
            $id = Utility::extractValue($values, 'id', 0);
            $entity = $this->_getEntityDetails($id);
            $type = Utility::extractValue($entity, 'subType', '');
        }
        
        $mapper = $this->_getAddressMapper($type);
        $response = $mapper->deleteAddress($values, $verbose);
        return $response;
    }
    
    /**
     * Helper function to retrieve the appropriate mapper for the address type
     * 
     * @param string $type
     * @return \AddressMapper
     */
    private function _getAddressMapper($type) {
        // Check that the type is valid
        if (!EnumAddressType::isValidValue($type)) {
            throw new Exception('Supplied address type is not valid: '.$type);
        }
            
        // The mapper to use will depend on the address type
        switch ($type) {
            case EnumAddressType::Physical:
            default:
                $mapper = new AddressPhysicalMapper($this->_config, $this->_db, $this->_log);
                break;
            case EnumAddressType::Phone:
                $mapper = new AddressPhoneMapper($this->_config, $this->_db, $this->_log);
                break;
            case EnumAddressType::Electronic:
                $mapper = new AddressElectronicMapper($this->_config, $this->_db, $this->_log);
                break;
        }
        
        return $mapper;
    }
   
    /**
     * Helper method that performs actual retrieval of address records
     * 
     * @param int    $parentId
     * @param int    $resultsPerPage
     * @param int    $start
     * @param array  $filter
     * @param string $order
     * @return boolean Array if successful, false on error
     */
    protected function _getAddresses($parentId, $type, $resultsPerPage, $start, $filter = null, $order = null) {
        switch($type) {
            case EnumAddressType::Physical:
                $tableName = 'tql_entity_addresses_physical';
                break;
            case EnumAddressType::Electronic:
                $tableName = 'tql_entity_addresses_electronic';
                break;
            case EnumAddressType::Phone:
                $tableName = 'tql_entity_addresses_phone';
                break;
            default:
                throw new \Tranquility\Exception('Supplied address type is not valid: '.$type);
        }
        
        $query  = "SELECT entity.*, address.*, trans.* \n";
        $query .= "FROM tql_entity AS entity, \n";
        $query .= "     tql_entity_xref AS xref, \n";
        $query .= "     ".$tableName." AS address, \n";
        $query .= "     tql_sys_trans_audit AS trans \n";
        $query .= "WHERE entity.id = address.id \n";
        $query .= "  AND entity.id = xref.childId \n";
        $query .= "  AND address.transactionId = trans.transactionId \n";
        $query .= "  AND entity.type = ? \n";
        $query .= "  AND entity.deleted = 0 \n";
        $query .= "  AND xref.parentId = ? \n";
        
        // Set SQL parameter values
        $filterValues = array(
            EnumEntityType::Address,
            $parentId            
        );
        
        // Add additional filters to the query if required
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
    
    public function transformResults($results, $verbose, $customFields = array()) {
        $results = parent::transformResults($results, $verbose, $customFields);
        
        $output = array();
        $output['addresses'] = $results;
        return $output;
    }
}
