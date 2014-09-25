<?php

/**
 * Abstract class to simulate enum constructs. Child classes should implement 
 * by defining public class constants
 * 
 * @abstract
 * @package \Tranquility
 * @author Andrew Patterson <patto@live.com.au>
 */

namespace Tranquility;

abstract class Enum {
    
    /**
     * Constructor
     * Throws exception to ensure we never instantiate an Enum
     * 
     * @throws \Tranquility\Exception
     */
    final public function __construct()
    {
        throw new Exception( 'Enum classes may not be instantiated.' );
    }
    
    /**
     * Returns the full list of values defined in the Enum
     * 
     * @param string $className Optional. If not supplied, will use the calling class
     * @return array
     * @throws \Tranquility\Exception
     */
    final public static function getEnumValues($className = null) {
        // If no class specified, use the current class
        if ($className === null) {
            $className = get_called_class();
        }
        
        // If class does not exist, throw an exception
        if (!class_exists($className)) {
            throw new Exception('Specified class "'.$className.'" does not exist!');
        }
        
        // Make sure the type provided is an Enum
        $reflector = new \ReflectionClass($className);
        if (!$reflector->isSubclassOf('\Tranquility\Enum')) {
            throw new Exception('Specified class "'.$className.'" is not a valid Enum class.');
        }
        
        // Return the list of constants
        return $reflector->getConstants();
    }
    
    /**
     * Determines whether the supplied value is a valid Enum value
     * 
     * @param mixed  $enumValue Value to verify
     * @param string $className Class name of the enum to check against (optional - defaults to current class)
     * @return boolean          True if value is defined in the enum, otherwise false
     */
    final public static function isValidValue($enumValue, $className = null) {
        // If no class specified, use the current class
        if ($className === null) {
            $className = get_called_class();
        }

        // Check class exists
        if (!class_exists($className)) {
            return false;
        }
        
        // Make sure the type provided is an Enum
        $reflector = new \ReflectionClass($className);
        if (!$reflector->isSubclassOf('\Tranquility\Enum')) {
            return false;
        }
        
        // Loop through each defined constant and attempt to match values
        foreach ($reflector->getConstants() as $label => $value) {
            if ($value == $enumValue) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Determines whether the supplied label is a defined Enum label
     * 
     * @param string $enumLabel Enum label to verify
     * @param string $className Class name of the enum to check against (optional - defaults to current class)
     * @return boolean          True if label is defined, otherwise false
     */
    final public static function isValidLabel($enumLabel, $className = null) {
        // If no class specified, use the current class
        if ($className === null) {
            $className = get_called_class();
        }

        // Check class exists
        if (!class_exists($className)) {
            return false;
        }
        
        // Make sure the type provided is an Enum
        $reflector = new \ReflectionClass($className);
        if (!$reflector->isSubclassOf('\Tranquility\Enum')) {
            return false;
        }
        
        // Loop through each defined constant and attempt to match values
        foreach ($reflector->getConstants() as $label => $value) {
            if ($label == $enumLabel) {
                return true;
            }
        }
        return false;
    }
}