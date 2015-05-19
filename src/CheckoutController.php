<?php namespace Bozboz\Ecommerce\Checkout;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Illuminate\Routing\Controller;

class CheckoutController extends Controller
{
	protected $checkout;

	public function __construct(CheckoutProcess $checkout)
	{
		$this->checkout = $checkout;
	}

	public function view()
	{
		$screen = $this->getScreenFromRoute();

		if ( ! $this->checkout->screenExists($screen)) {
			throw new NotFoundHttpException;
		}

		if ( ! $this->checkout->isValidScreen($screen)) {
			return $this->checkout->redirectToActiveScreen();
		}

		if ($this->checkout->canSkipScreen($screen)) {
			return $this->checkout->redirectToNextScreen($screen);
		}

		return $this->checkout->viewScreen($screen);
	}

	public function process($screen = '/')
	{
		$screen = $this->getScreenFromRoute();

		if ( ! $this->checkout->screenExists($screen)) {
			throw new NotFoundHttpException;
		}

		if ( ! $this->checkout->isValidScreen($screen) || ! $this->checkout->canProcess($screen)) {
			return $this->checkout->redirectToActiveScreen();
		}

		return $this->checkout->processScreen($screen);
	}

	protected function getScreenFromRoute()
	{
		$route = \Route::current();

		$screenIdentifier = trim(str_replace($route->getPrefix(), '', $route->getPath()), '/');

		return $screenIdentifier ?: '/';
	}
}
