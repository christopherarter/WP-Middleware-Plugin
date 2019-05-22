<?php

namespace WPMiddleware;

/**
 * This class is used to designate
 * message & status of rejection. 
 * It is called by a helper function.
 */
class MiddlewareRejection
{
    public $message;
    public $status;

    public function __construct(string $message = 'Unauthorized', $status = 401)
    {
        $this->message = $message;
        $this->status  = (int) $status;
    }
}