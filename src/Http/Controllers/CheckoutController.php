<?php

namespace Bozboz\Ecommerce\Checkout\Http\Controllers;

use Bozboz\Ecommerce\Checkout\CheckoutProcess;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CheckoutController extends Controller
{
	protected $checkout;

	public function __construct(CheckoutProcess $checkout)
	{
		$this->checkout = $checkout;
	}

	public function view()
	{
		$screen = $this->getScreenAliasFromRoute();

		try {
			return $this->checkout->viewScreen($screen);
		} catch (InvalidScreenException $e) {
			return $this->checkout->redirectToStart();
		}

	}

	public function process($screen = '/')
	{
		$screen = $this->getScreenAliasFromRoute();

		try {
			return $this->checkout->processScreen($screen);
		} catch (CannotProcessException $e) {
			return $this->checkout->redirectToStart();
		}
	}

	protected function getScreenAliasFromRoute()
	{
		return \Route::currentRouteName();
	}
}
