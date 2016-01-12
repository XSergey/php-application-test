<?php
namespace Test\Services;

use Test\Services\Service;
use Test\Http\Response;

class ResponseService extends Service
{
	public function register()
	{
		$this->container->registerSingleton(['Test\Http\Response', 'response'], function($container)
		{
			return new Response($container->get('request'), $container->get('app')->getCharset(), $container->get('signer'));
		});
	}
}