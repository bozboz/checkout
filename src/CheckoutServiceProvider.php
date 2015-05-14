<?php namespace Bozboz\Ecommerce\Checkout;

use Illuminate\Support\ServiceProvider;

class CheckoutServiceProvider extends ServiceProvider
{
	public function register()
	{
		$this->app->singleton(
			'checkout',
			'Bozboz\Ecommerce\Checkout\CheckoutProcess'
		);

		$this->app->bind(CheckoutController::class, function($app)
		{
			return new CheckoutController($app['checkout']);
		});
	}
}
