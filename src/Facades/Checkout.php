<?php namespace Bozboz\Ecommerce\Checkout\Facades;

use Illuminate\Support\Facades\Facade;

class Checkout extends Facade
{
	static public function getFacadeAccessor()
	{
		return 'checkout';
	}
}
