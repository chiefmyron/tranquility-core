<?php

/**
 * Enumeration of entity types:
 *   - Person
 *   - Address
 *   - Content
 *
 * @package \Tranquility\Enum\System
 * @author Andrew Patterson <patto@live.com.au>
 */

namespace Tranquility\Enum\System;

class EntityType extends \Tranquility\Enum {
    
    const Person = 'person';
    const Address = 'address';
    const Content = 'content';
    const User = 'user';
}