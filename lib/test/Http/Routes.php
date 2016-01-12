<?php
namespace Test\Http;

use Closure;

class Routes
{
	protected $groups = [];
	protected  $routes = [];
	protected $namedRoutes = [];
    
	public function getRoutes()
	{
		return $this->routes;
	}

    public function hasNamedRoute($name)
	{
		return isset($this->namedRoutes[$name]);
	}

	public function getNamedRoute($name)
	{
		if(!isset($this->namedRoutes[$name]))
		{
			throw new RuntimeException(vsprintf("%s(): No route named [ %s ] has been defined.", [__METHOD__, $name]));
		}

		return $this->namedRoutes[$name];
	}

	public function group(array $options, Closure $routes)
	{
		$this->groups[] = $options;

		$routes($this);

		array_pop($this->groups);
	}

	protected function getRealMethodName($method)
	{
		return str_replace(['namespace'], ['setNamespace'], $method);
	}

    protected function registerRoute(array $methods, $route, $action, $name = null)
	{
		$route = new Route($methods, $route, $action, $name);

		$this->routes[] = $route;

		if($name !== null)
		{
			$this->namedRoutes[$name] = $route;
		}

		if(!empty($this->groups))
		{
			foreach($this->groups as $group)
			{
				foreach($group as $option => $value)
				{
					$route->{$this->getRealMethodName($option)}($value);
				}
			}
		}

		return $route;
	}

	public function get($route, $action, $name = null)
	{
		return $this->registerRoute(['GET', 'HEAD', 'OPTIONS'], $route, $action, $name);
	}

	public function post($route, $action, $name = null)
	{
		return $this->registerRoute(['POST', 'OPTIONS'], $route, $action, $name);
	}

	public function put($route, $action, $name = null)
	{
		return $this->registerRoute(['PUT', 'OPTIONS'], $route, $action, $name);
	}

	public function patch($route, $action, $name = null)
	{
		return $this->registerRoute(['PATCH', 'OPTIONS'], $route, $action, $name);
	}

	public function delete($route, $action, $name = null)
	{
		return $this->registerRoute(['DELETE', 'OPTIONS'], $route, $action, $name);
	}

	public function all($route, $action, $name = null)
	{
		return $this->registerRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'], $route, $action, $name);
	}

    public function methods(array $methods, $route, $action, $name = null)
	{
		return $this->registerRoute($methods, $route, $action, $name);
	}
}