<?php
namespace Test\View;

use Test\FileSystem;
use Test\View\Template as Compiler;
use Test\View\Renderer;

class Template extends Renderer
{
	protected $cachePath;

	protected $blocks = [];

	protected $openBlocks = [];

	public function __construct(FileSystem $fileSystem, $cachePath)
	{
		$this->fileSystem = $fileSystem;

		$this->cachePath = $cachePath;
	}

	protected function getCompiledPath($view)
	{
		return $this->cachePath . '/' . md5($view) . '.php';
	}

	protected function needToCompile($view, $compiled)
	{
		return !$this->fileSystem->exists($compiled) || $this->fileSystem->lastModified($compiled) < $this->fileSystem->lastModified($view);
	}

	protected function compile($view)
	{
		(new Compiler($this->fileSystem, $this->cachePath, $view))->compile();
	}

	public function open($name)
	{
		ob_start() && $this->openBlocks[] = $name;
	}

	public function close()
	{
		return $this->blocks[array_pop($this->openBlocks)][] = ob_get_clean();
	}

	public function output($name)
	{
		$parent = $this->close();

		$output = current($this->blocks[$name]);

		unset($this->blocks[$name]);

		if(!empty($parent))
		{
			$output = str_replace('__PARENT__', $parent, $output);
		}

		echo $output;
	}

    public function render($__view__, array $__variables__)
	{
		$compiled = $this->getCompiledPath($__view__);

		if($this->needToCompile($__view__, $compiled))
		{
			$this->compile($__view__);
		}

		return parent::render($compiled, array_merge($__variables__, ['__renderer__' => $this]));
	}
}