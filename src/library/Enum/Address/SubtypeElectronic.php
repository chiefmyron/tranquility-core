<?php

/**
 * Enumeration of physical address sub-types:
 *   - Home
 *   - Postal
 *   - Billing
 *
 * @package \Tranquility\Enum\Address
 * @author Andrew Patterson <patto@live.com.au>
 */

namespace Tranquility\Enum\Address;

class SubtypeElectronic extends \Tranquility\Enum {
    
    const Email = 'email';
    const URL = 'url';
    
}