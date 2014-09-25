<?php

namespace Tranquility;

/**
 * Utility static class containing useful shortcut methods
 */
class Utility {
    
    /**
     * Constructor
     * Throws exception to ensure we never instantiate
     * 
     * @throws \Tranquility\Exception
     */
    final private function __construct()
    {
        throw new Exception( '\Tranquility\Utility class may not be instantiated.' );
    }
    
    /**
     * Extracts the value for a specified key from an array or object
     *
     * @param mixed $object The array or object the value is stored in
     * @param string $key The identifier for the value
     * @param mixed $default The value to return if no value is found in $object
     * @param string $data_type [Optional] The datatype to cast the value to
     * @return mixed The extracted value
     */
    public static function extractValue($object, $key, $default = null, $data_type = '') {
        $value = null;

        // Determine if the object is an array or an object
        if (is_array( $object ) && isset($object[$key]) ) {
            $value = $object[$key];              // Suppress notice if key is not set
        } elseif (is_object( $object ) && isset($object->$key) ){
            $value = $object->$key;              // Suppress notice if key is not set
        }

        // If no value was extracted, return default value
        if (is_null( $value )) {
            return $default;
        }

        // Perform type casting
        $data_type = strtolower($data_type);
        switch( $data_type ) {
            case 'string':
            case 'str':
                // Cast to string
                $value = strval( $value );
                break;
            case 'integer':
            case 'int':
                // Cast to integer
                $value = intval( $value );
                break;
           case 'float':
           case 'double':
               // Cast to decimal
               $value = floatval( $value );
               break;
           case 'boolean':
           case 'bool':
               // Cast to boolean
               $value = (bool) $value;
               break;
           default:
               // No type casting necessary
               break;
        }

        // Return extracted value
        return $value;
    }
    
    /**
     * Determines if the specified key exists for an object.
     * 
     * Object can be either an array or an object.
     * 
     * @param mixed $object
     * @param string $key
     * @return boolean
     */                                       
    public static function keyExists( $object, $key ) {
        if (is_array( $object ) && isset( $object[$key] )) {
            return true;
        } else if (is_object( $object ) && isset( $object->$key )) {
            return true;
        } else {
            return false;
        }
    }
    
    public static function getDbMinDateTime($dbType = null) {
        return '0000-00-00 00:00:00';
    }
    
    public static function getDbMaxDateTime() {
        return '9999-12-31 23:59:59';
    }
}