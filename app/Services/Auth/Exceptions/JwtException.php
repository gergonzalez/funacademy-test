<?php

/**
 * Custom Jwt Exception.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App\Services\Auth\Exceptions;

use Exception;

class JwtException extends Exception
{
    /**
     * Create the new JwtGuard guard.
     *
     * @param string $message
     * @param int    $code
     */
    public function __construct($message = 'An error occurred', $code = 0)
    {
        parent::__construct($message, $code, null);
    }
}
