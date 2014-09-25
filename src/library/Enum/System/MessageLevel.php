<?php

namespace Tranquility\Enum\System;

/**
 * Defines the valid levels for messages returned in service responses
 *   error
 *   warning
 *   information
 *   success
 */

class MessageLevel extends \Tranquility\Enum {
    
    const Error = 'error';
    const Warning = 'warning';
    const Info = 'information';
    const Success = 'success';
    
}
