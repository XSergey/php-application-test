<?php
namespace Test\View;

use Closure;
//use RuntimeException;

use Test\Common\NamespacedFileLoaderTrait;
use Test\FileSystem;

class ViewFactory
{
	use NamespacedFileLoaderTrait;

	protected $charset;

	protected $renderers = ['.php' => 'Test\View\Renderer'];

	protected $cachePath;

	protected $globalVariables = [];

	protected $viewCache = [];

	protected $rendererInstances;

	public function __construct(FileSystem $fileSystem, $path, $charset = 'UTF-8')
	{
		$this->fileSystem = $fileSystem;

		$this->path = $path;

		$this->globalVariables['__viewfactory__'] = $this;

		$this->setCharset($charset);
	}

	public function getCharset()
	{
		return $this->charset;
	}

	public function setCharset($charset)
	{
		$this->globalVariables['__charset__'] = $this->charset = $charset;

		return $this;
	}

	protected function prependRenderer($extention, $renderer)
	{
		$this->renderers = [$extention => $renderer] + $this->renderers;
	}

	protected function appendRenderer($extention, $renderer)
	{
		$this->renderers =  $this->renderers + [$extention => $renderer];
	}

	public function registerRenderer($extention, $renderer, $prepend = true)
	{
		$prepend ? $this->prependRenderer($extention, $renderer) : $this->appendRenderer($extention, $renderer);

		return $this;
	}

	public function getCachePatch()
	{
		return $this->cachePath;
	}

	public function setCachePath($cachePath)
	{
		$this->cachePath = $cachePath;

		return $this;
	}

	public function assign($name, $value)
	{
		$this->globalVariables[$name] = $value;

		return $this;
	}

	protected function getViewPathAndExtension($view, $throwException = true)
	{
        if(!isset($this->viewCache[$view]))
		{
			// Loop throught the avaiable extensions and check if the view exists
            
			foreach($this->renderers as $extension => $renderer)
			{
				$paths = $this->getCascadingFilePaths($view, $extension);
                
				foreach($paths as $path)
				{
                    if($this->fileSystem->exists($path))
					{
						return $this->viewCache[$view] = [$path, $extension];
					}
				}
			}

			// We didn't find the view so we'll throw an exception or return false

			if($throwException)
			{
				//throw new RuntimeException(vsprintf("%s(): The [ %s ] view does not exist.", [__METHOD__, $view]));
			}

			return false;
		}
        
		return $this->viewCache[$view];
	}

	protected function resolveRenderer($extension)
	{
		if(!isset($this->rendererInstances[$extension]))
		{
			$this->rendererInstances[$extension] = $this->rendererFactory($this->renderers[$extension]);
		}
        
		return $this->rendererInstances[$extension];
	}
    
    protected function rendererFactory($renderer)
	{   
		return $renderer instanceof Closure ? $renderer() : new $renderer;
	}

	public function exists($view)
	{
		return $this->getViewPathAndExtension($view, false) !== false;
	}

	public function create($view, array $variables = [])
	{
		list($path, $extension) = $this->getViewPathAndExtension($view);
        
		return new View($path, $variables + $this->globalVariables, $this->resolveRenderer($extension));
	}

	public function render($view, array $variables = [])
	{
		return $this->create($view, $variables)->render();
	}
}