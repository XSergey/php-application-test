<?php

namespace Test\View;

//use mako\view\renderers\EscaperTrait;
use Test\View\RendererInterface;

class Renderer implements RendererInterface
{
	//use EscaperTrait;

	public function render($__view__, array $__variables__)
	{
		extract($__variables__, EXTR_REFS | EXTR_SKIP);

		ob_start();

		include($__view__);

		return ob_get_clean();
	}
}