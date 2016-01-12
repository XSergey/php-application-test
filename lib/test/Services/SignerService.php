<?php
namespace Test\Services;

use Test\Services\Service;
use Test\Security\Signer;

class SignerService extends Service
{
	/**
	 * {@inheritdoc}
	 */

	public function register()
	{
		$this->container->registerSingleton(['Test\Security\Signer', 'signer'], function($container)
		{
			return new Signer($container->get('config')->get('application.secret'));
		});
	}
}