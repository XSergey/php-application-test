<?php
namespace Test\Services;

use Test\Services\Service;
use Test\View\ViewFactory;
use Test\View\Template;

class ViewFactoryService extends Service
{
	public function register()
	{
		$this->container->registerSingleton(['Test\View\ViewFactory', 'view'], function($container)
		{
			$app = $container->get('app');

			$applicationPath = $app->getPath();

			$fileSystem = $container->get('filesystem');

			// Create factory instance

			$factory = new ViewFactory($fileSystem, dirname($applicationPath) . '/resources/views', $app->getCharset());
            
			// Register template renderer

			$factory->registerRenderer('.tpl.php', function() use ($applicationPath, $fileSystem)
			{
				new Template($fileSystem, $applicationPath . '/storage/cache/views');
			});

			// Return factory instance

			return $factory;
		});
	}
}