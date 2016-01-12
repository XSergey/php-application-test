<?php
namespace app\controllers;

use Test\Http\Controller;
use Test\View\ViewFactory;

class Index extends Controller
{
	public function welcome(ViewFactory $view)
    {
        return $view->render('home');
    }
}