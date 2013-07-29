<?php 

namespace Testapp\REST;

use Testapp\REST\Router;

class Route
{

    public function __construct(Router $router) {
        $this->router = $router;
    }

    /**
     * Register a GET route with the router.
     *
     * @param  string|array  $route
     * @param  mixed         $action
     * @return void
     */
    public function get($route, $action)
    {
        $this->register('GET', $route, $action);
    }

    /**
     * Register a POST route with the router.
     *
     * @param  string|array  $route
     * @param  mixed         $action
     * @return void
     */
    public function post($route, $action)
    {
        $this->register('POST', $route, $action);
    }

    /**
     * Register a PUT route with the router.
     *
     * @param  string|array  $route
     * @param  mixed         $action
     * @return void
     */
    public function put($route, $action)
    {
        $this->register('PUT', $route, $action);
    }

    /**
     * Register a DELETE route with the router.
     *
     * @param  string|array  $route
     * @param  mixed         $action
     * @return void
     */
    public function delete($route, $action)
    {
        $this->register('DELETE', $route, $action);
    }

    /**
     * Register a route that handles any request method.
     *
     * @param  string|array  $route
     * @param  mixed         $action
     * @return void
     */
    public function any($route, $action)
    {
        $this->register('*', $route, $action);
    }

    /**
     * Register a HTTPS route with the router.
     *
     * @param  string        $method
     * @param  string|array  $route
     * @param  mixed         $action
     * @return void
     */
    public function secure($method, $route, $action)
    {
        // stop when not secure
        if (!$this->router->secure())
            return;
        
        $this->register($method, $route, $action);
    }

    /**
     * Register a controller with the router.
     *
     * @param  string|array  $controllers
     * @param  string|array  $defaults
     * @return void
     */
    public function controller($controllers, $defaults = 'index')
    {
        $this->router->controller($controllers, $defaults);
    }

    /**
     * Register a route with the router.
     * 
     * @param  string        $method
     * @param  string|array  $route
     * @param  mixed         $action
     * @return void
     */
    public function register($method, $route, $action)
    {
        // If the developer is registering multiple request methods to handle
        // the URI, we'll spin through each method and register the route
        // for each of them along with each URI and action.
        if (is_array($method))
        {
            foreach ($method as $http)
            {
                $this->router->route($http, $route, $action);
            }
            return;
        }
        
        foreach ((array) $route as $uri) {
            $this->router->route($method, $uri, $action);
        }
    }

}