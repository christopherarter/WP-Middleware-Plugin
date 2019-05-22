<?php

namespace WPMiddleware;

/**
 * This is the abstract class
 * that catches the request 
 * at the `rest_pre_dispatch` hook
 * and passes it to the `rest_pre_dispatch`
 * hook to check the request, and either
 * allow or reject it in a series of 
 * callbacks. This class is extended
 * in `Middleware.php`
 */
abstract class AbstractMiddleware
{
    use MiddlewareMethods;

    /**
     * Internal middleware
     * container.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Inbound request object.
     *
     * @var WP_REST_Request
     */
    protected $request;

    /**
     * Request method for
     * inbound request. This
     * property is used to determine
     * correct method for route.
     *
     * @var string
     */
    protected $methodsToProtect = [];

    /**
     * Route input provided to compare
     * inbound route against. This could
     * be a string or an array.
     *
     * @var string
     */
    protected $routeInput;

    /**
     * Determines if all requests
     * should be rejected.
     *
     * @var boolean
     */
    protected $rejectAll = false;
    /**
     * Array of MiddlewareRejection
     * objects.
     *
     * @var array
     */
    protected $rejections = [];


    /**
     * Response object provided
     * to check hook
     *
     * @var \WP_HTTP_Response
     */
    protected $response;

    /**
     * Hook to use to check
     * against request property.
     *
     * @var string
     */
    protected $outboundHook = 'rest_post_dispatch';

    /**
     * Hook used to check
     * request against callbacks.s
     *
     * @var string
     */
    protected $inboundHook = 'rest_pre_dispatch';

    public function __construct()
    {
        add_action( $this->outboundHook, [$this, 'check'] );
        add_filter( $this->inboundHook, [$this, 'setRequest'], 0, 3);
    }

    /**
     * Grab the inbound WP_REST_Request
     * and save it to a private property
     * for the `checkRoute` method to inject
     * into callbacks.  
     *
     * @param array ...$input
     * @return void
     */
    public function setRequest(...$input) : void
    {
        $this->request = $input[2];
    }

    /**
     * Compare a string of $route,
     * against an array of functions
     * to call.
     *
     * @param mixed $route
     * @param array $functions
     * @return void
     */
    public function guard($route, array $functions = []): Middleware
    {
        $this->routeInput = $route;
        $this->middleware = $functions;
        return $this;
    }

    /**
     * Checks the callbacks registered
     * to a given route. If the response
     * is a MiddlewareRejection, then it
     * is saved into the $rejections property
     * to be counted in the `check` method.
     *
     * @return void
     */
    protected function checkRoute() : void
    {
        if ( $this->requestMethodMatch() && $this->routePathMatch() ) {
            if ( is_array($this->routeInput) ) {
                foreach ( $this->routeInput as $route) {
                    $this->checkCallbacks();
                }
            } else {
                $this->checkCallbacks();
            }
        }
    }

    /**
     * Iterate through the 
     * callbacks in the $middleware
     * property and see if they
     * return MiddlewareRejection class.
     *
     * @return void
     */
    protected function checkCallbacks()
    {
        foreach ( $this->middleware as $callback ) {
            if ( function_exists( $callback ) ) {
                $result = call_user_func($callback, $this->request, $this->response);
                if ( $result instanceof MiddlewareRejection ) {
                    $this->rejections[] = $result;
                }
            }
        }
    }

    /**
     * This method takes the 
     * current $request object property
     * and determines if a given input string
     * or array of strings containing methods
     * match the request's method.
     *
     * @return boolean
     */
    protected function requestMethodMatch() : bool
    {
        return ( in_array( $this->request->get_method(), $this->methodsToProtect ) );
    }

    /**
     * Determines if there is
     * a matching route.
     *
     * @return boolean
     */
    protected function routePathMatch() : bool
    {
        $requestRoute = $this->response->get_matched_route();

        if ( ( is_array($this->routeInput) && in_array( $requestRoute, $this->routeInput ))
        || $this->routeInput == $requestRoute ) {
            return true;
        }
        
        return false;
    }

    /**
     * Rejection response factory. Takes an inbound
     * WP_HTTP_Response object and sets the properties
     * as a rejection response.
     *
     * @param \WP_HTTP_Response $response
     * @param \MiddlewareRejection $MiddlewareRejection
     * @return void
     */
    public function rejectWpResponse(\WP_HTTP_Response $response, MiddlewareRejection $MiddlewareRejection) : \WP_HTTP_Response
    {
        $response->set_status((int) $MiddlewareRejection->status );
        $response->set_data($MiddlewareRejection->message);
        return $response;
    }

    /**
     * Check inbound request
     * against registered middleware.
     *
     * @param \WP_HTTP_Response $response
     * @return \WP_HTTP_Response
     */
    public function check( \WP_HTTP_Response $response) : \WP_HTTP_Response
    {
        $this->response = $response;

        if ( $this->rejectAll) {
            return $this->rejectWpResponse($this->response, new MiddlewareRejection());
        }

        /**
         * If no HTTP Method setting method
         * was used, just default to the inbound
         * request. This allows `guard()` to protect
         * all methods.
         */
        if (!$this->methodsToProtect) {
            $this->methodsToProtect[] = $this->request->get_method();
        }

        /**
         * Check route against all
         * callbacks and populate rejections
         * property.
         */
        $this->checkRoute();

        
        // check rejections property.
        if ( count($this->rejections) > 0) {

            // send response to response factory.
            return $this->rejectWpResponse($this->response, $this->rejections[0]);
        }

        return $this->response;
    }
}

