<?php namespace Bozboz\Ecommerce\Checkout;

use Illuminate\Session\Store;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;

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
	 * Retrieve and set the completed screens from session
	 *
	 * @param Illuminate\Session\Store  $store
	 * @param Illuminate\Routing\Redirector  $redirector
	 * @param Illuminate\Routing\UrlGenerator  $url
	 */
	public function __construct(Store $store, Redirector $redirector, UrlGenerator $url)
	{
		$this->store = $store;
		$this->redirect = $redirector;
		$this->url = $url;
	}

	/**
	 * Set route alias of the process, for generating URLs
	 *
	 * @param  string  $alias
	 */
	public function setRouteAlias($alias)
	{
		$this->routeAlias = $alias;
		$this->completedScreens = $this->store->get($alias . '.completed_screens', []);
	}

	/**
	 * Add a screen to the checkout process, identified by its slug and IoC
	 * binding, or concrete class.
	 *
	 * @param  string  $slug
	 * @param  string  $binding
	 * @param  string  $label
	 * @return void
	 */
	public function add($slug, $binding, $label = null)
	{
		$this->screens[$slug] = $binding;
		$this->screenLabels[$slug] = $label ?: $binding;
		$this->screenIdentifiers[] = $slug;
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
		$screensCompleted = count($this->completedScreens);
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
		return in_array($identifier, $this->completedScreens);
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
		$activeScreen = $this->screenIdentifiers[count($this->completedScreens)];

		return $this->redirectTo($activeScreen);
	}

	/**
	 * Redirect to next screen
	 *
	 * @param  string  $identifier
	 * @return Illiminate\Http\RedirectResponse
	 */
	public function redirectToNextScreen($identifier)
	{
		$index = array_search($identifier, $this->screenIdentifiers);

		$nextScreen = $this->screenIdentifiers[$index + 1];

		return $this->redirectTo($nextScreen);
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
	 * Process the screen, using its identifier
	 *
	 * @param  string  $identifier
	 * @return Illiminate\Http\RedirectResponse
	 */
	public function processScreen($identifier)
	{
		try {
			$response = $this->getScreen($identifier)->process();
		} catch (ValidationException $e) {
			return $this->redirect->back()->withErrors($e->getErrors())->withInput();
		}

		if ( ! in_array($identifier, $this->completedScreens)) {
			$this->store->push($this->routeAlias . '.completed_screens', $identifier);
		}

		return $this->redirectToNextScreen($identifier);
	}

	/**
	 * Get the URL to a screen, using its identifier
	 *
	 * @param  string  $identifier
	 * @return string
	 */
	public function urlToScreen($identifier)
	{
		return $this->url->route($this->routeAlias, [$identifier]);
	}

	/**
	 * Redirect to a screen, by its identifier
	 *
	 * @param  string  $identifier
	 * @return Illuminate\Http\RedirectResponse
	 */
	protected function redirectTo($identifier)
	{
		return $this->redirect->route($this->routeAlias, $identifier);
	}
}
