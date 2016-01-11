<?php
namespace Test;

use Test\FileSystem;
use Test\Common\NamespacedFileLoaderTrait;

class Config
{
    use NamespacedFileLoaderTrait;
    
    protected $fileSystem;
    //protected $path; //used by trait
    protected $environment;
    protected $configuration = [];
    
    public function __construct(FileSystem $fileSystem, $path, $environment = null)
	{
		$this->fileSystem = $fileSystem;

		$this->path = $path;

		$this->environment = $environment;
	}
    
    public function get($key, $default = null)
	{
		list($file, $path) = $this->parseKey($key);
        
		if(!isset($this->configuration[$file]))
		{
			$this->load($file);
		}

		return $path === null ? $this->configuration[$file] : $this->getArr($this->configuration[$file], $path, $default);
	}
    
    protected function parseKey($key)
	{
		return (strpos($key, '.') === false) ? [$key, null] : explode('.', $key, 2);
	}
    
    protected function load($file)
	{
		// Load configuration

		foreach($this->getCascadingFilePaths($file) as $path)
		{
			if($this->fileSystem->exists($path))
			{
				$config = $this->fileSystem->includeFile($path);

				break;
			}
		}

		/*if(!isset($config))
		{
			throw new RuntimeException(vsprintf("%s(): The [ %s ] config file does not exist.", [__METHOD__, $file]));
		}*/

		// Merge environment specific configuration

		if($this->environment !== null)
		{
			$namespace = strpos($file, '::');

			$namespaced = ($namespace === false) ? $this->environment . '.' . $file : substr_replace($file, $this->environment . '.', $namespace + 2, 0);

			foreach($this->getCascadingFilePaths($namespaced) as $path)
			{
				if($this->fileSystem->exists($path))
				{
					$config = array_replace_recursive($config, $this->fileSystem->includeFile($path));

					break;
				}
			}
		}

		$this->configuration[$file] = $config;
	}
    
    public function getArr(array $array, $path, $default = null)
	{
		$segments = explode('.', $path);

		foreach($segments as $segment)
		{
			if(!is_array($array) || !isset($array[$segment]))
			{
				return $default;
			}

			$array = $array[$segment];
		}

		return $array;
	}
    
    
}