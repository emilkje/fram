<?php 

namespace Testapp\REST;

use Testapp\REST\Route;
use Testapp\REST\Request;

class Router
{

    /**
     * The URI for the current request.
     *
     * @var string
     */
    public $uri;

    /**
     * The base URL for the current request.
     *
     * @var string
     */
    public $base;

    /**
     * Was the user routed yet?
     */
    private $routed = FALSE;

    /**
     * Request object
     */
    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    /**
     * Get the URI for the current request.
     *
     * @return string
     */
    public function uri()
    {
        if (!is_null($this->uri))
            return $this->uri;
        
        $this->uri = $this->request->getPathInfo();

        // Remove leading and trailing slashes
        $this->uri = trim($this->uri, '/');
        
        if ($this->uri == '')
            $this->uri = '/';
        
        return $this->uri;
    }

    /**
     * Get the base URL for the current request.
     *
     * @return string
     */
    public function base($uri = '')
    {
        if (!is_null($this->base))
            return $this->base . $uri;

        if ($this->request->server->get('HTTP_HOST'))
        {
            $this->base = Router::secure() ? 'https' : 'http';
            $this->base .= '://' . $this->request->server->get('HTTP_HOST');
        }
        else
        {
            $this->base = 'http://localhost/';
        }
        
        return $this->base . $uri;
    }

    /**
     * Check if the the request is requested by HTTPS 
     *
     * @return bool
     */
    public function secure()
    {
        $https = $this->request->server->get('HTTPS');
        return !empty($https) && $https != 'off';
    }

    /**
     * Get the request method for the current request.
     *
     * @return string
     */
    public function method()
    {
        return strtoupper($this->request->server->get("REQUEST_METHOD"));
    }

    /**
     * Match the route and execute the action
     * 
     * @param  string  $method
     * @param  string  $route
     * @param  mixed   $action
     * @return void
     */
    public function route($method, $route, $action)
    {
        // If a previous route was matched, we can skip all routes with a lower
        // priority.
        if ($this->routed)
        {
            return;
        }
        
        // We can ignore this route if the request method does not match
        if ($method != '*' && strtoupper($method) != $this->method())
        {
            return;
        }
        
        $route = trim($route, '/');
        
        if ($route == '')
            $route = '/';
        
        // Of course literal route matches are the quickest to find, so we will
        // check for those first. If the destination key exists in the routes
        // array we can just return that route now.
        if ($route == $this->uri())
        {
            $this->call($action);
            return;
        }
        
        // We only need to check routes with regular expression since all others
        // would have been able to be matched by the search for literal matches
        // we just did before we started searching.
        if (strpos($route, '(') !== FALSE)
        {
            $patterns = array(
                '(:num)' => '([0-9]+)', 
                '(:any)' => '([a-zA-Z0-9\.\-_%=]+)', 
                '(:all)' => '(.*)', 
                '/(:num?)' => '(?:/([0-9]+))?', 
                '/(:any?)' => '(?:/([a-zA-Z0-9\.\-_%=]+))?', 
                '/(:all?)' => '(?:/(.*))?'
            );
            
            $route = str_replace(array_keys($patterns), array_values($patterns), $route);
            
            // If we get a match we'll return the route and slice off the first
            // parameter match, as preg_match sets the first array item to the
            // full-text match of the pattern.
            if (preg_match('#^' . $route . '$#', $this->uri(), $parameters))
            {
                $this->call($action, array_slice($parameters, 1));
                return;
            }
        }
    }

    /**
     * Execute an action matched by the router
     *
     * @param  mixed   $action
     * @param  mixed   $parameters
     * @return void
     */
    private function call($action, $parameters = array())
    {
        if (is_callable($action))
        {
            // The action is an anonymous function, let's execute it.
            echo call_user_func_array($action, $parameters);
        }
        else if (is_string($action) && strpos($action, '@'))
        {
            list($controller, $method) = explode('@', $action);
            $class = basename($controller);
            
            // Controller delegates may use back-references to the action parameters,
            // which allows the developer to setup more flexible routes to various
            // controllers with much less code than would be usual.
            if (strpos($method, '(:') !== FALSE)
            {
                foreach ($parameters as $key => $value)
                {
                    $method = str_replace('(:' . ($key + 1) . ')', $value, $method, $count);
                    if ($count > 0)
                    {
                        unset($parameters[$key]);
                    }
                }
            }
            
            // Default controller method if left empty.
            if (!$method)
            {
                $method = 'index';
            }
            
            // Load the controller class file if needed.
            if (!class_exists($class))
            {
                if (file_exists("controllers/$controller.php"))
                {
                    include ("controllers/$controller.php");
                }
            }
            
            // The controller class was still not found. Let the next routes handle the
            // request.
            if (!class_exists($class))
            {
                return;
            }
            
            $instance = new $class();
            echo call_user_func_array(array($instance, $method), $parameters);
        }
        
        // The current route was matched. Ignore new routes.
        $this->routed = TRUE;
    }

    /**
     * Match the route with a controller and execute a method
     *
     * @param  string|array  $controllers
     * @param  string        $defaults
     * @return void
     */
    public function controller($controllers, $defaults = 'index')
    {
        foreach ((array) $controllers as $controller)
        {
            // If the current URI does not match this controller we can simply skip
            // this route.
            if (strpos(strtolower($this->uri()), strtolower($controller)) === 0)
            {
                // First we need to replace the dots with slashes in the controller name
                // so that it is in directory format. The dots allow the developer to use
                // a cleaner syntax when specifying the controller. We will also grab the
                // root URI for the controller's bundle.
                $controller = str_replace('.', '/', $controller);
                
                // Automatically passes a number of arguments to the controller method
                $wildcards = str_repeat('/(:any?)', 6);
                
                // Once we have the path and root URI we can build a simple route for
                // the controller that should handle a conventional controller route
                // setup of controller/method/segment/segment, etc.
                $pattern = trim($controller . $wildcards, '/');
                
                // Rregister the controller route with a wildcard method so it is 
                // available on every request method.
                $this->route('*', $pattern, "$controller@(:1)");
            }
        }
    }

}