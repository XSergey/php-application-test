<?php
namespace Test;

use Test\Container;
use Test\Config;
use Test\FileSystem;
use Test\Http\Router;
use Test\Http\Dispatcher;

class Application
{
    protected static $instance;
    
    protected $container;
    
    protected $applicationPath;
    
    public function __construct($applicationPath)
    {
        $this->applicationPath = $applicationPath;
        
        $this->initialize();
        $this->configure();
        $this->registerServices();
    }
    
    public static function create($applicationPath)
    {
        if(empty(static::$instance))
            return static::$instance = new static($applicationPath);
    }
    
    public function run()
    {
        ob_start();
        $request = $this->container->get('request');
        
        list($filters, $routes) = $this->loadRouting();
		list($route, $parameters) = (new Router($routes))->route($request);
        
		(new Dispatcher($request, $this->container->get('response'), $filters, $route, $parameters, $this->container))->dispatch()->send();
    }
    
    protected function initialize()
    {
        $this->container = new Container();
        $this->container->registerInstance( ['Test\Container', 'container'], $this->container );
        $this->container->registerInstance( ['Test\Application', 'app'], $this );
        $this->container->registerInstance( ['Test\FileSystem', 'filesystem'], $fileSystem = new FileSystem() );
        $this->config = new Config($fileSystem, $this->applicationPath.'/config', $this->getEnvironment() );
        $this->container->registerInstance( ['Test\Config', 'config'], $this->config );
    }
    
    protected function configure()
    {
        $config = $this->config->get('application');
        
		$this->charset = $config['charset'];

		mb_language('uni');

		mb_regex_encoding($this->charset);

		mb_internal_encoding($this->charset);

		date_default_timezone_set($config['timezone']);

		$this->setLanguage($config['default_language']);
    }
    
    protected function registerServices()
    {
        $this->serviceRegistrar('core');
    }
     
    protected function serviceRegistrar($type)
    {
        foreach($this->config->get('application.services.'.$type) as $service)
        {
            (new $service($this->container))->register();
        }
    }
    
    public function setLanguage(array $language)
	{
		$this->language = $language['strings'];

		foreach($language['locale'] as $category => $locale)
		{
			setlocale($category, $locale);
		}
	}
    
    public function getEnvironment()
	{
		return getenv('PHP_APPLICATION_ENV') ?: null;
	}
    
    protected function loadRouting()
	{
		return [$this->loadFilters(), $this->loadRoutes()];
	}
    
    protected function loadFilters()
	{
		return [];
	}
    
    protected function loadRoutes()
	{
		$loader = function($app, $container, $routes)
		{
			include $this->applicationPath . '/Http/routes.php';

			return $routes;
		};

		return $loader($this, $this->container, $this->container->get('routes'));
	}
    
    public function getCharset()
	{
		return $this->charset;
	}
    
    public function getPath()
    {
        return $this->applicationPath;
    }
}