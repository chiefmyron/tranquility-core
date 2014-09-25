<?php

/**
 * Enumeration of main address types:
 *   - Physical
 *   - Electronic
 *   - Phone
 *
 * @package \Tranquility\Enum\Address
 * @author Andrew Patterson <patto@live.com.au>
 */

namespace Tranquility\Enum\Address;

class Type extends \Tranquility\Enum {
    
    const Physical = 'physical';
    const Electronic = 'electronic';
    const Phone = 'phone';
    
}