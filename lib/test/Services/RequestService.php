<?php
namespace Test\Services;

use Test\Services\Service;
use Test\Http\Request;

class RequestService extends Service
{
	public function register()
	{
        $requestBuild = function($container)
		{
			$config = $container->get('config');
            
			$request = new Request(['languages' => $config->get('application.languages')], null/*$container->get('signer')*/);
            
			//$request->setTrustedProxies($config->get('application.trusted_proxies'));
            
			return $request;
		};

		$this->container->registerSingleton(['Test\Http\Request','request'], new Request());
	
    }
}