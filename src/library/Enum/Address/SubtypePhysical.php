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

class SubtypePhysical extends \Tranquility\Enum {
    
    const Home = 'home';
    const Postal = 'postal';
    const Billing = 'billing';
    
}