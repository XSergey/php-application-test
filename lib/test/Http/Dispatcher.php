<?php

/**
 * @copyright  Frederic G. Østby
 * @license    http://www.makoframework.com/license
 */

namespace Test\Http;

use Closure;

use Test\Http\Request;
use Test\Http\Response;
use Test\Http\Route;
use Test\Container;

class Dispatcher
{
	protected $request;

	protected $response;

	protected $filters;

	protected $route;

	protected $parameters;

	protected $container;

	protected $skipAfterFilters = false;

	public function __construct(Request $request, Response $response, $filters = null, Route $route, array $parameters = [], Container $container = null)
	{
		$this->request = $request;

		$this->response = $response;

		//$this->filters = $filters;

		$this->route = $route;

		$this->parameters = $parameters;

		$this->container = $container ?: new Container;
	}

	protected function resolveFilter($filter)
	{
		$filter = $this->filters->get($filter);

		if(!($filter instanceof Closure))
		{
			$filter = [$this->container->get($filter), 'filter'];
		}

		return $filter;
	}

	protected function executeFilter($filter)
	{
		// Parse the filter function call

		list($filter, $parameters) = $this->parseFunction($filter);

		// Get the filter from the filter collection

		$filter = $this->resolveFilter($filter);

		// Execute the filter and return its return value

		return $this->container->call($filter, $parameters);
	}

	protected function beforeFilters()
	{
		foreach($this->route->getBeforeFilters() as $filter)
		{
			$returnValue = $this->executeFilter($filter);

			if(!empty($returnValue))
			{
				return $returnValue;
			}
		}
	}

	protected function afterFilters()
	{
		foreach($this->route->getAfterFilters() as $filter)
		{
			$this->executeFilter($filter);
		}
	}

	protected function dispatchClosure(Closure $closure)
	{
		$this->response->body($this->container->call($closure, $this->parameters));
	}

	protected function dispatchController($controller)
	{
		list($controller, $method) = explode('::', $controller, 2);
        
		$controller = $this->container->get($controller);
        
		// Execute the before filter if we have one

		if(method_exists($controller, 'beforeFilter'))
		{
			$returnValue = $this->container->call([$controller, 'beforeFilter']);
		}

		if(empty($returnValue))
		{
			// The before filter didn't return any data so we can set the
			// response body to whatever the route action returns

			$this->response->body($this->container->call([$controller, $method], $this->parameters));

			// Execute the after filter if we have one

			if(method_exists($controller, 'afterFilter'))
			{
				$this->container->call([$controller, 'afterFilter']);
			}
		}
		else
		{
			// The before filter returned data so we'll set the response body to whatever it returned
			// and tell the dispatcher to skip all after filters

			$this->response->body($returnValue);

			$this->skipAfterFilters = true;
		}
	}

	public function dispatch()
	{
		$returnValue = $this->beforeFilters();
        
		if(!empty($returnValue))
		{
			$this->response->body($returnValue);
		}
		else
		{
			$action = $this->route->getAction();

			if($action instanceof Closure)
			{
				$this->dispatchClosure($action);
			}
			else
			{
				$this->dispatchController($action);
			}

			if(!$this->skipAfterFilters)
			{
				$this->afterFilters();
			}
		}

		return $this->response;
	}
}