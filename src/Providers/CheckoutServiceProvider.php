<?php

namespace Bozboz\Ecommerce\Checkout\Providers;

use Bozboz\Ecommerce\Checkout\Http\Controllers\CheckoutController;
use Illuminate\Support\ServiceProvider;

class CheckoutServiceProvider extends ServiceProvider
{
	public function register()
	{
		$this->app->singleton(
			'checkout.router',
			'Bozboz\Ecommerce\Checkout\CheckoutRouter'
		);

		$this->app->bind(
			'checkout.process',
			'Bozboz\Ecommerce\Checkout\CheckoutProcess'
		);

		$this->app->bind(CheckoutController::class, function($app)
		{
			$currentRoute = $app['router']->current();

			if ($currentRoute) {
				$process = $app['checkout.router']->getProcess($currentRoute);
			} else {
				$process = $app['checkout.process'];
			}

			return new CheckoutController($process);
		});
	}
}
