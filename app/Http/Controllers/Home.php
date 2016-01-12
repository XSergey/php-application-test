<?php
namespace app\controllers;

use Test\Application\Controller;
use Test\View\ViewFactory;

class Home extends Controller
{
	public function index(ViewFactory $view)
    {
        return $view->render('home');
    }
}