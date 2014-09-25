<?php

/**
 * Security group (ACL role) model
 *
 * @uses BusinessObjectMapper
 * @package API
 * @author Andrew Patterson <patto@live.com.au>
 */

use \Tranquility\Response\Response as Response;
use \Tranquility\Enum\System\MessageLevel as EnumMessageLevel;
use \Tranquility\Enum\System\HttpStatusCode as EnumStatusCodes;

class SecurityRoleMapper extends BusinessObjectMapper {
    
    /**
     * Default mapping for column names to API field names
     * 
     * @return array Keys are API fields, and values
     */
    public function getDefaultFields() {
        $fields = array(
            'id' => 'id',
            'name' => 'roleName'
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
            'name' => 'roleName',
            'parentRoleId' => 'parentRoleId',
        );
        return $fields;
    }
    
    /**
     * Returns a single security role
     * 
     * @param int $id
     * @param boolean $verbose
     * @return \Tranquility\Response\Response
     */
    public function getSecurityRoleById($id, $verbose = false) {
        $filter = array();
        $filter[] = array(
            'column' => 'id',
            'operator' => '=',
            'value' => $id
        );
        
        // Retrieve the role
        $results = $this->_getSecurityRoles(1, 0, $filter);
        
        // Set up response
        $response = new Response();
        $response->setResponseCode(EnumStatusCodes::OK);
        if (is_array($results) > 0) {
            $response->setContent($this->transformResults($results, $verbose));
            $response->addMessage(10101, 'message_10101_single_security_group_retrieved_successfully', EnumMessageLevel::Info);
        }
        
        // Return response
        return $response;
    }
    
    /**
     * Returns a list of security roles
     * 
     * @param int $resultsPerPage
     * @param int $start
     * @param boolean $verbose
     * @param array $filter
     * @return \Tranquility\Response\Response
     */
    public function getSecurityRoleList($resultsPerPage, $start, $verbose = false, $filter = null) {
        // Default order is by person ID
        $order = 'aclRoles.id';
        
        // Retrieve the list
        $results = $this->_getSecurityRoles($resultsPerPage, $start, $filter, $order);
        
        // Set up response
        $response = new Response();
        $response->setResponseCode(EnumStatusCodes::OK);
        if (is_array($results) > 0) {
            $response->setContent($this->transformResults($results, $verbose));
            $response->addMessage(10100, 'message_10100_security_group_list_retrieved_successfully', EnumMessageLevel::Info);
        }
        
        // Return response
        return $response;
    }
    
    /**
     * Helper method that performs actual retrieval of security role records
     * 
     * @param int$resultsPerPage
     * @param int $start
     * @param array $filter
     * @param string $order
     * @return mixed Array of results if successful, otherwise false
     */
    protected function _getSecurityRoles($resultsPerPage, $start, $filter = null, $order = null) {
        // Construct SQL statement to retrieve list of people
        $query  = "SELECT aclRoles.* \n";
        $query .= "FROM tql_sys_acl_roles AS aclRoles \n";
        $query .= "WHERE 1 = 1 \n";
        
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
    
    public function transformResults($results, $verbose) {
        $results = parent::transformResults($results, $verbose);
        
        $output = array();
        $output['securityRoles'] = $results;
        return $output;
    }
}
