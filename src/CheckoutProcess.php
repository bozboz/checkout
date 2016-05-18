<?php

namespace Bozboz\Ecommerce\Checkout;

use Illuminate\Routing\Redirector;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Session\Store;
use Illuminate\Support\Collection;

class CheckoutProcess
{
	/**
	 * @var Illuminate\Session\Store
	 */
	protected $store;

	/**
	 * @var Illuminate\Routing\Redirector
	 */
	protected $redirect;

	/**
	 * @var Illuminate\Routing\UrlGenerator
	 */
	protected $url;

	/**
	 * @var Illuminate\Routing\Router
	 */
	protected $router;

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
	protected $defaultController = 'Bozboz\Ecommerce\Checkout\CheckoutController';

	/**
	 * @var string
	 */
	protected $routeAlias;

	/**
	 * @param Illuminate\Session\Store  $store
	 * @param Illuminate\Routing\Redirector  $redirector
	 * @param Illuminate\Routing\UrlGenerator  $url
	 * @param Illuminate\Routing\Router  $router
	 */
	public function __construct(Store $store, Redirector $redirector, UrlGenerator $url, Router $router)
	{
		$this->store = $store;
		$this->redirect = $redirector;
		$this->url = $url;
		$this->router = $router;
	}

	/**
	 * Set route alias of the process, for generating URLs
	 *
	 * @param  string  $alias
	 */
	public function setRouteAlias($alias)
	{
		$this->routeAlias = $alias;
	}

	/**
	 * Add a screen to the checkout process, registering a route, identified by
	 * its slug and IoC binding, or concrete class.
	 *
	 * @param  string  $slug
	 * @param  string  $binding
	 * @param  string  $label
	 * @param  array  $params
	 * @return void
	 */
	public function add($slug, $binding, $label = null, $params = [])
	{
		$this->screens[$slug] = $binding;
		$this->screenLabels[$slug] = $label ?: $binding;
		$this->screenIdentifiers[] = $slug;

		$params = $this->formParameters($params, ['view', 'process']);

		$this->router->get($slug, [
			'uses' => $this->defaultController . '@view',
			'as' => $this->getScreenAlias($slug)
		] + $params['view']);

		$this->router->post($slug, [
			'uses' => $this->defaultController . '@process'
		] + $params['process']);
	}

	/**
	 * Form a parameters array into a new array indexed by the provided $toForm
	 * parameter
	 *
	 * @param  array  $params
	 * @param  array  $toForm
	 * @return array
	 */
	protected function formParameters(array $params, array $toForm)
	{
		if (array_intersect(array_keys($params), $toForm)) {
			return array_merge(array_fill_keys($toForm, []), $params);
		} else {
			return array_fill_keys($toForm, $params);
		}
	}

	/**
	 * Check if screen exists, by its identifier
	 *
	 * @param  string  $identifier
	 * @return boolean
	 */
	public function screenExists($identifier)
	{
		return array_key_exists($identifier, $this->screens);
	}

	/**
	 * Check if screen is valid, by its identifier
	 *
	 * @param  string  $identifier
	 * @return boolean
	 */
	public function isValidScreen($identifier)
	{
		$screensCompleted = count($this->getCompletedScreens());
		$index = array_search($identifier, $this->screenIdentifiers);

		return $index === 0 OR $index <= $screensCompleted;
	}

	/**
	 * Check if screen can be skipped, by its identifier
	 *
	 * @param  string  $identifier
	 * @return boolean
	 */
	public function canSkipScreen($identifier)
	{
		return $this->getScreen($identifier)->canSkip();
	}

	/**
	 * Check if screen is complete, by its identifier
	 *
	 * @param  string  $identifier
	 * @return boolean
	 */
	public function isScreenComplete($identifier)
	{
		return in_array($identifier, $this->getCompletedScreens());
	}

	/**
	 * Render the screen's response, using its identifier
	 *
	 * @param  string  $identifier
	 * @return mixed
	 */
	public function viewScreen($identifier)
	{
		return $this->getScreen($identifier)->view()->with([
			'screens' => $this->screenLabels,
			'checkout' => $this,
			'currentScreen' => $identifier
		]);
	}

	/**
	 * Resolve the screen from the IoC container
	 *
	 * @param  string  $identifier
	 * @return mixed
	 */
	public function getScreen($identifier)
	{
		return app($this->screens[$identifier]);
	}

	/**
	 * Redirect to active screen
	 *
	 * @return Illiminate\Http\RedirectResponse
	 */
	public function redirectToActiveScreen()
	{
		$activeScreen = $this->screenIdentifiers[count($this->getCompletedScreens())];

		return $this->redirectTo($activeScreen);
	}

	/**
	 * Determine if screen, by its identifier, can be processed
	 *
	 * @param  string  $identifier
	 * @return boolean
	 */
	public function canProcess($identifier)
	{
		return $this->getScreen($identifier) instanceof Processable;
	}

	/**
	 * Process the screen, using its identifier.
	 *
	 * - If a ValidationException is thrown, redirect back with errors
	 * - Otheriwse, mark the screen as complete
	 * - Determine the next screen, and redirect to it
	 *
	 * @param  string  $identifier
	 * @return Illiminate\Http\RedirectResponse|mixed
	 */
	public function processScreen($identifier)
	{
		try {
			$response = $this->getScreen($identifier)->process();
		} catch (ValidationException $e) {
			return $this->redirectTo($identifier)->withErrors($e->getErrors())->withInput();
		}

		$this->markScreenAsComplete($identifier);

		if ( ! is_null($response)) return $response;

		$nextScreen = $this->getNextScreen($identifier);

		while($this->canSkipScreen($nextScreen)) {
			$this->markScreenAsComplete($nextScreen);
			$nextScreen = $this->getNextScreen($nextScreen);
		}

		return $this->redirectTo($nextScreen);
	}

	/**
	 * Get the URL to a screen, using its identifier
	 *
	 * @param  string  $identifier
	 * @return string
	 */
	public function urlToScreen($identifier)
	{
		return $this->url->route($this->getScreenAlias($identifier));
	}

	/**
	 * Mark screen as complete
	 *
	 * @param  string  $identifier
	 * @return void
	 */
	protected function markScreenAsComplete($identifier)
	{
		if ( ! in_array($identifier, $this->getCompletedScreens())) {
			$this->store->push($this->routeAlias . '.completed_screens', $identifier);
		}
	}

	/**
	 * Fetch completed screens from the session
	 *
	 * @return array
	 */
	protected function getCompletedScreens()
	{
		static $completedScreens;
		return $completedScreens ?: $completedScreens = $this->store->get($this->routeAlias . '.completed_screens', []);
	}

	/**
	 * Get the alias for a screen, from its identifier
	 *
	 * @param  string  $identifier
	 * @return string
	 */
	protected function getScreenAlias($identifier)
	{
		$alias = [$this->routeAlias];

		if ($identifier !== '/') {
			$alias[] = $identifier;
		}

		return implode('.', $alias);
	}

	/**
	 * Redirect to a screen, by its identifier
	 *
	 * @param  string  $identifier
	 * @return Illuminate\Http\RedirectResponse
	 */
	protected function redirectTo($identifier)
	{
		return $this->redirect->to($this->urlToScreen($identifier));
	}

	/**
	 * Get the next screen from the current screen's identifier
	 *
	 * @param  string  $identifier
	 * @return string
	 */
	protected function getNextScreen($identifier)
	{
		$index = array_search($identifier, $this->screenIdentifiers);

		return $this->screenIdentifiers[$index + 1];
	}
}
