<?php

$routes->group(['namespace' => 'app\controllers'], function($routes)
{
	$routes->get('/', 'Home::index');
});

//$routes->get('/', 'Index::welcome');