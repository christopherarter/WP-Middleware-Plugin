<?php

/**
 * Rejection factory helper.
 * Returns a MiddlewareRejection
 * to be used by Middleware class
 * to determine if callback has failed.
 *
 * @param mixed $message
 * @param integer $status
 * @return void
 */
function reject($message, $status = 403)
{
    return new \WPMiddleware\MiddlewareRejection( $message, (int) $status );
}

/**
 * Helper to instantiate 
 * the child class of
 * AbstractMiddleware.
 *
 * @return void
 */
function middleware()
{
    return new \WPMiddleware\Middleware();
}