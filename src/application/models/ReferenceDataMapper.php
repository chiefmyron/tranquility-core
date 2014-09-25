<?php

/**
 * Model for reference data lookups, listings and validation
 *
 * @uses BusinessObjectMapper
 * @package API
 * @author Andrew Patterson <patto@live.com.au>
 */

class ReferenceDataMapper extends BusinessObjectMapper {
    
    // List of valid reference data tables that can be accessed via this mapper
    private $_referenceDataTableNames = array (
        'tql_cd_locales',
        'tql_cd_timezones'
    );
    
    public function getCodeValues($tableName, $codeColumnName, $valueColumnName, $order = '', $filter = array()) {
        // Check table is allowed
        if (!in_array($tableName, $this->_referenceDataTableNames)) {
            throw new \Tranquility\Exception('Table supplied is not a valid reference data table: '.$tableName);
        }
        
        // Construct query
        $query  = "SELECT ".$codeColumnName." AS code, ".$valueColumnName." AS value \n";
        $query .= "FROM ".$tableName." \n";
        $query .= "WHERE 1 \n"; // Simplifies adding optional filter criteria
        
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
            $query .= "ORDER BY ".$pdo->quote($order)." \n";
        }
        
        // Prepare and execute query
        $results = $this->_db->select($query, $filterValues);
        return $results;
    }
    
    public function isValidCode($code, $tableName, $codeColumnName) {
        // Check table is allowed
        if (!in_array($tableName, $this->_referenceDataTableNames)) {
            throw new \Tranquility\Exception('Table supplied is not a valid reference data table: '.$tableName);
        }
        
        // Construct query
        $query  = "SELECT * \n";
        $query .= "FROM ".$tableName." \n";
        $query .= "WHERE ".$codeColumnName." = ?";
        $values = array($code);
        
        // Prepare and execute query
        $results = $this->_db->select($query, $values);
        if (count($results) > 0) {
            return true;
        }
        
        return false;
    }
}