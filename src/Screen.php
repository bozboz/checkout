<?php namespace Bozboz\Ecommerce\Checkout;

abstract class Screen
{
	abstract public function view();

	/**
	 * Determine if screen can be skipped
	 *
	 * @return boolean
	 */
	public function canSkip()
	{
		return false;
	}
}
