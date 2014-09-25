<?php

namespace Tranquility\Enum\System;

/**
 * Defines HTTP status codes used by API
 *   200 - OK
 *   400 - Bad request
 *   401 - Unauthorised
 *   403 - Forbidden
 *   404 - Not found
 *   405 - Method not allowed
 *   409 - Conflict
 *   500 - Internal server error
 */

class ExternalServiceResponseType extends \Tranquility\Enum {
    
    const Success = 'success';
    const UnexpectedError = 'error';
    const ExceededApiLimit = 'limitExceeded';
    const AuthenticationFailed = 'accessDenied';
    const InvalidRequest = 'invalidRequest';
}