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

class HttpStatusCode extends \Tranquility\Enum {
    
    const OK = 200;
    const BadRequest = 400;
    const Unauthorized = 401;
    const Forbidden = 403;
    const NotFound = 404;
    const MethodNotAllowed = 405;
    const Conflict = 409;
    const InternalServerError = 500;
}