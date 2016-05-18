<?php

namespace Bozboz\Ecommerce\Checkout\Providers;

use Bozboz\Ecommerce\Checkout\CheckoutController;
use Illuminate\Support\ServiceProvider;

class CheckoutServiceProvider extends ServiceProvider
{
	public function register()
	{
		$this->app->singleton(
			'checkout',
			'Bozboz\Ecommerce\Checkout\CheckoutRouter'
		);

		$this->app->bind(CheckoutController::class, function($app)
		{
			$currentRoute = $app['router']->current();

			$process = $app['checkout']->getProcess($currentRoute);

			return new CheckoutController($process);
		});
	}
}
