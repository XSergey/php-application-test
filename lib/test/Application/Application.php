<?php
namespace Test;

use Test\Container;
use Test\Config;
use Test\FileSystem;
use Test\Http\Request;

class Application
{
    protected static $instance;
    
    protected $container;
    
    protected $applicationPath;
    
    public function __construct($applicationPath)
    {
        $this->applicationPath = $applicationPath;
        $this->boot();
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
        var_dump($this->container);
    }
    
    protected function boot()
    {
        $this->initialize();
        $this->configure();
        $this->registerServices();
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

		// Set internal charset

		$this->charset = $config['charset'];

		mb_language('uni');

		mb_regex_encoding($this->charset);

		mb_internal_encoding($this->charset);

		// Set default timezone

		date_default_timezone_set($config['timezone']);

		// Set locale information

		$this->setLanguage($config['default_language']);
    }
    
    protected function registerServices()
    {
        $this->registerAppServices();
        //or for example other like CLI services...
    }
        
    protected function registerAppServices()
    {
		$this->serviceRegistrar('core');
    }
    
    protected function serviceRegistrar($type)
    {
        foreach($this->config->get('application.services.'.$type) as $service)
        {
            //var_dump($service);
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
		return getenv('MAKO_ENV') ?: null;
	}
}