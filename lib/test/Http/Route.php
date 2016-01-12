<?php
namespace Test\Http;

use Closure;

class Route
{
	protected $methods;
    protected $route;
	protected $action;
	protected $name;
	protected $namespace;
	protected $prefix;
	protected $hasTrailingSlash;
	protected $constraints = [];
	protected $beforeFilters = [];
	protected $afterFilters = [];

	public function __construct(array $methods, $route, $action, $name = null)
	{
		$this->methods = $methods;

		$this->route = $route;

		$this->action = $action;

		$this->name = $name;

		$this->hasTrailingSlash = (substr($route, -1) === '/');
	}

	public function getMethods()
	{
		return $this->methods;
	}

    public function getRoute()
	{
		return $this->prefix . $this->route;
	}

	public function getAction()
	{
		if($this->action instanceof Closure || empty($this->namespace))
		{
			return $this->action;
		}

		return $this->namespace . $this->action;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getBeforeFilters()
	{
		return $this->beforeFilters;
	}

	public function getAfterFilters()
	{
		return $this->afterFilters;
	}

	public function setNamespace($namespace)
	{
		$this->namespace .= $namespace . '\\';

		return $this;
	}

	public function prefix($prefix)
	{
		if(!empty($prefix))
		{
			$this->prefix .= '/' . trim($prefix, '/');
		}

		return $this;
	}

	public function when(array $constraints)
	{
		$this->constraints = $constraints + $this->constraints;

		return $this;
	}

	public function before($filters)
	{
		$this->beforeFilters = array_merge($this->beforeFilters, (array) $filters);

		return $this;
	}

	public function after($filters)
	{
		$this->afterFilters = array_merge($this->afterFilters, (array) $filters);

		return $this;
	}

	public function allows($method)
	{
		return in_array($method, $this->methods);
	}

    public function hasTrailingSlash()
	{
		return $this->hasTrailingSlash;
	}

	public function getRegex()
	{
		$route = $this->getRoute();

		if(strpos($route, '?'))
		{
			$route = preg_replace('/\/{(\w+)}\?/', '(?:/{$1})?', $route);
		}

		if(!empty($this->constraints))
		{
			foreach($this->constraints as $key => $constraint)
			{
				$route = str_replace('{' . $key . '}', '(?P<' . $key . '>' . $constraint . ')', $route);
			}
		}

		$route = preg_replace('/{((\d*[a-z_]\d*)+)}/i', '(?P<$1>[^/]++)', $route);

		if($this->hasTrailingSlash)
		{
			$route .= '?';
		}

		return '#^' . $route . '$#su';
	}
}
