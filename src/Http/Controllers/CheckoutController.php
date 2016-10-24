<?php

namespace Bozboz\Ecommerce\Checkout\Http\Controllers;

use Bozboz\Ecommerce\Checkout\CannotProcessException;
use Bozboz\Ecommerce\Checkout\CheckoutProcess;
use Bozboz\Ecommerce\Checkout\EmptyCartException;
use Bozboz\Ecommerce\Checkout\InvalidScreenException;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Request;
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
			return $this->checkout->viewScreen($screen, Request::has('redirect'));
		} catch (EmptyCartException $e) {
			return redirect()->route('cart');
		} catch (InvalidScreenException $e) {
			return $this->checkout->redirectToStart();
		}

	}

	public function process()
	{
		$screen = $this->getScreenAliasFromRoute();

		try {
			return $this->checkout->processScreen($screen);
		} catch (EmptyCartException $e) {
			return redirect()->route('cart');
		} catch (InvalidScreenException $e) {
			return $this->checkout->redirectToStart();
		}
	}

	protected function getScreenAliasFromRoute()
	{
		return \Route::currentRouteName();
	}
}
