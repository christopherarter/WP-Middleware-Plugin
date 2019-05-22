## Wordpress REST API Middleware
---
WP Middleware is a middleware package for creating middleware checks for the REST API. It passes a request to a series of callbacks against which you can check.

#### Basic example
```
middleware()->get('/wp/v2/posts', ['check_foo', 'check_bar']);

// callback check
function check_foo($request) {
    if($request->get_param('foo') != 'foo' ){
        return reject();
    }
}

// callback check
function check_bar($request) {
    if($request->get_param('bar') != 'bar' ){
        return reject();
    }
}
```

`middleware()->get()` checks all `GET` requests to that given route. To check against all HTTP methods, use `middleware()-guard()`.

#### Callback Parameters

All callbacks must have at least 1 request parameter, which will recieve an instance of the `WP_REST_Request` class. You will be able to use all of the methods of this class within your callback to perform checks.

_Optional_
You may also accept a second parameter, which is the outgoing response if you choose to modify it in any way based on the inbound request. This second parameter will be given an instance of `WP_HTTP_Response`.

#### Protecting Multiple Routes

To protect multiple routes with the same series of checks

```
$middlewareStack = ['check_foo', 'check_bar'];

middleware()->guard([
    '/wp/v2/posts',
    '/wp/v2/users' ], 
    $middlewareStack);
```


#### Protecting Multiple HTTP Methods
If you would like to protect multiple HTTP Methods in one call, you can use the `methods()` helper and pass in an array of methods to target:
``` 
middleware()->methods(['POST', 'PUT'], '/wp/v2/users', $middlewareStack);
```
#### Customize Rejection Message and Status
You can return a rejection in any callback using the `reject()` function. If you would like to pass a custom message or status, you can pass those in as paramaters as below:
`return reject("Failed this specific check.", 400);`

The default status is `401` but you may override as you wish.

#### Reject all requests
While Wordpress includes core functions to achieve this too, if you would like to keep turning on / off endpoints within the middleware logic area, you can also use as below:
```
middleware()->reject('/wp/v2/users');
```

#### Available HTTP Method Helpers ####
All method helpers require two parameters:
- `string|array $routeInput`
- `array $callbacks = []`

##### Avaiable methods
-   `guard()` - Protects all HTTP methods
-   `get()`
-   `post()`
-   `put()`
-   `patch()`
-   `delete()`
-   `head()`
#### Other helper methods
-   `methods()` - Protect multiple HTTP Methods
-   `reject()` - Reject all requests to this route.