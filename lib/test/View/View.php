<?php
namespace Test\View;

//use mako\view\renderers\RendererInterface;

class View
{
	protected $path;

	protected $variables;

	protected $renderer;

	public function __construct($path, array $variables, RendererInterface $renderer)
	{
		$this->path = $path;

		$this->variables = $variables;

		$this->renderer = $renderer;
	}

	public function getRenderer()
	{
		return $this->renderer;
	}

	public function assign($name, $value)
	{
		$this->variables[$name] = $value;

		return $this;
	}

    public function render()
	{
		return $this->renderer->render($this->path, $this->variables);
	}
}