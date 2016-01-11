<?php

define('PHP_APPLICATION_START', microtime(true));
define('PHP_APPLICATION_PATH', dirname(__DIR__).'/app');

Test\Application::create(PHP_APPLICATION_PATH)->run();