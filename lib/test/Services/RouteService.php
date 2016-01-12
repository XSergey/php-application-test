<?php
namespace Test\Services;

use Test\Services\Service;

class RouteService extends Service
{
	public function register()
	{
		$this->container->registerSingleton(['Test\Http\Routes', 'routes'], 'Test\Http\Routes');
	}
}