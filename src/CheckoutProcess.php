<?php

namespace Bozboz\Ecommerce\Checkout;

use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Bozboz\Ecommerce\Orders\OrderRepository;

class CheckoutProcess
{
	/**
	 * @var Illuminate\Routing\Redirector
	 */
	protected $redirect;

	/**
	 * @var Illuminate\Routing\UrlGenerator
	 */
	protected $url;

	/**
	 * @var Illuminate\Container\Container
	 */
	protected $container;

	/**
	 * @var array
	 */
	protected $screens = [];

	/**
	 * @var array
	 */
	protected $screenIdentifiers = [];

	/**
	 * @var array
	 */
	protected $completedScreens = [];

	/**
	 * @var string
	 */
	protected $routeAlias;

	/**
	 * @param Illuminate\Routing\Redirector  $redirector
	 * @param Illuminate\Routing\UrlGenerator  $url
	 * @param Illuminate\Routing\Router  $router
	 */
	public function __construct(Container $container, Redirector $redirector, UrlGenerator $url)
	{
		$this->container = $container;
		$this->redirect = $redirector;
		$this->url = $url;
	}

	public function setRepository($repo)
	{
		$this->repo = $repo;
	}

	/**
	 * Add a screen to the checkout process, registering a route, identified by
	 * its alias and IoC binding, or concrete class.
	 *
	 * @param  string  $alias
	 * @param  string  $binding
	 * @param  string  $label
	 * @return void
	 */
	public function addScreen($alias, $binding, $label = null)
	{
		$this->screens[$alias] = $binding;
		$this->screenLabels[$alias] = $label ?: $binding;
		$this->screenIdentifiers[] = $alias;
	}

	/**
	 * Check if screen exists, by its screenAlias
	 *
	 * @param  string  $screenAlias
	 * @return boolean
	 */
	public function screenExists($screenAlias)
	{
		return array_key_exists($screenAlias, $this->screens);
	}

	/**
	 * Check if screen is valid, by its screenAlias
	 *
	 * @param  string  $screenAlias
	 * @return boolean
	 */
	public function canAccessScreen($order, $screenAlias)
	{
		$requestedIndex = $this->getScreenIndex($screenAlias);

		$currentIndex = $this->getNextScreenIndex($order->getCompletedScreen());

		return $requestedIndex <= $currentIndex;
	}

	/**
	 * Check if screen can be skipped, by its screenAlias
	 *
	 * @param  string  $screenAlias
	 * @return boolean
	 */
	public function canSkipScreen($screenAlias)
	{
		return $this->getScreen($screenAlias)->canSkip();
	}

	/**
	 * Render the screen's response, using its screenAlias
	 *
	 * @param  string  $screenAlias
	 * @return mixed
	 */
	public function viewScreen($screenAlias)
	{
		$order = $this->repo->lookupOrder();

		if ( ! $order || ! $this->canAccessScreen($order, $screenAlias)) {
			throw InvalidScreenException($screenAlias);
		}

		return $this->getScreen($screenAlias)->view($order)->with([
			'screens' => $this->screenLabels,
			'checkout' => $this,
			'currentScreen' => $screenAlias
		]);
	}

	/**
	 * Resolve the screen from the IoC container
	 *
	 * @param  string  $screenAlias
	 * @return mixed
	 */
	public function getScreen($screenAlias)
	{
		return $this->container->make($this->screens[$screenAlias]);
	}

	/**
	 * Redirect to active screen
	 *
	 * @return Illiminate\Http\RedirectResponse
	 */
	public function redirectToStart()
	{
		$activeScreen = reset($this->screenIdentifiers);

		return $this->redirectTo($activeScreen);
	}

	/**
	 * Determine if screen, by its screenAlias, can be processed
	 *
	 * @param  string  $screenAlias
	 * @return boolean
	 */
	public function canProcessScreen($screenAlias)
	{
		return $this->getScreen($screenAlias) instanceof Processable;
	}

	/**
	 * Process the screen, using its screenAlias.
	 *
	 * - If a ValidationException is thrown, redirect back with errors
	 * - Otheriwse, mark the screen as complete
	 * - Determine the next screen, and redirect to it
	 *
	 * @param  string  $screenAlias
	 * @return Illiminate\Http\RedirectResponse|mixed
	 */
	public function processScreen($screenAlias)
	{
		$screen = $this->getScreen($screenAlias);

		if ($this->repo->hasOrder()) {
			$order = $this->repo->lookupOrder();
		} else {
			$order = $screen->lookupOrder();
		}

		if ( ! $order) {
			throw new CannotProcessException;
		}

		if ( ! $this->canAccessScreen($order, $screenAlias)) {
			throw new InvalidScreenException($screenAlias);
		}

		try {
			$response = $screen->process($order);
		} catch (ValidationException $e) {
			return $this->redirectTo($screenAlias)->withErrors($e->getErrors())->withInput();
		}

		$this->markScreenAsComplete($order, $screenAlias);

		if ( ! is_null($response)) return $response;

		$nextScreen = $this->getNextScreen($screenAlias);

		while($this->canSkipScreen($nextScreen)) {
			$this->markScreenAsComplete($order, $nextScreen);
			$nextScreen = $this->getNextScreen($nextScreen);
		}

		return $this->redirectTo($nextScreen);
	}

	/**
	 * Get the URL to a screen, using its screenAlias
	 *
	 * @param  string  $screenAlias
	 * @return string
	 */
	public function urlToScreen($screenAlias)
	{
		return $this->url->route($screenAlias);
	}

	/**
	 * Mark screen as complete if it has not been reached before
	 *
	 * @param  Bozboz\Ecommerce\Orders\Order  $order
	 * @param  string  $screenAlias
	 * @return void
	 */
	protected function markScreenAsComplete($order, $screenAlias)
	{
		$requestedIndex = $this->getScreenIndex($screenAlias);
		$currentIndex = $this->getScreenIndex($order->getCompletedScreen());

		if ($requestedIndex >= $currentIndex) {
			$order->markScreenAsComplete($screenAlias);
		}
	}

	/**
	 * Redirect to a screen, by its screenAlias
	 *
	 * @param  string  $screenAlias
	 * @return Illuminate\Http\RedirectResponse
	 */
	protected function redirectTo($screenAlias)
	{
		return $this->redirect->to($this->urlToScreen($screenAlias));
	}

	/**
	 * Get the next screen from the current screen's screenAlias
	 *
	 * @param  string  $screenAlias
	 * @return string
	 */
	private function getNextScreen($screenAlias)
	{
		$nextIndex = $this->getNextScreenIndex($screenAlias);

		return $this->screenIdentifiers[$nextIndex];
	}

	private function getScreenIndex($screenAlias)
	{
		return array_search($screenAlias, $this->screenIdentifiers);
	}

	private function getNextScreenIndex($screenAlias)
	{
		$index = $this->getScreenIndex($screenAlias);
		return $index !== false ? $index + 1 : null;
	}
}
