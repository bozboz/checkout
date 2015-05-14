<?php namespace Bozboz\Ecommerce\Checkout;

use Illuminate\Session\Store;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Router;
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
	 * @var Illuminate\Routing\Router
	 */
	protected $route;

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
	protected $urlPrefix = 'checkout';

	/**
	 * @var string
	 */
	protected $viewAction = 'Bozboz\Ecommerce\Checkout\CheckoutController@view';

	/**
	 * @var string
	 */
	protected $processAction = 'Bozboz\Ecommerce\Checkout\CheckoutController@process';

	/**
	 * Retrieve and set the completed screens from session
	 *
	 * @param Illuminate\Session\Store  $store
	 * @param Illuminate\Routing\Redirector  $redirector
	 * @param Illuminate\Routing\Router  $router
	 * @param Illuminate\Routing\UrlGenerator  $url
	 */
	public function __construct(Store $store, Redirector $redirector, Router $router, UrlGenerator $url)
	{
		$this->store = $store;
		$this->redirect = $redirector;
		$this->route = $router;
		$this->url = $url;

		$this->registerRoutes();

		$this->completedScreens = $this->store->get('completed_screens', []);
	}

	/**
	 * Register routes for checkout
	 *
	 * @return void
	 */
	protected function registerRoutes()
	{
		$params = [
			'prefix' => $this->urlPrefix
		];

		$this->route->group($params, function()
		{
			$this->route->get('{screen?}', $this->viewAction);
			$this->route->post('{screen?}', $this->processAction);
		});
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
		return $this->getScreen($identifier)->view()
		 . $this->renderMenu($identifier);
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
		return $this->redirectTo($this->getActiveScreen());
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
		} catch (\Exception $e) {
			return $this->redirect->back()->withErrors([
				'error' => $e->getMessage()
			])->withInput();
		}

		if ( ! in_array($identifier, $this->completedScreens)) {
			$this->store->push('completed_screens', $identifier);
		}

		return $this->redirectTo($this->getNextScreen($identifier));
	}

	/**
	 * Render a menu
	 *
	 * @param  string  $identifier
	 * @return string
	 */
	protected function renderMenu($currentScreenIdentifier)
	{
		$menu = [];

		foreach($this->screenLabels as $screen => $label) {
			$active = ($currentScreenIdentifier == $screen);
			if ($this->isValidScreen($screen)) {
				$menu[] = sprintf('<a href="%s" class="%s">%s</a>',
					$this->url->action($this->viewAction, [$screen]),
					implode(' ', [
						$active ? 'selected' : '',
						$this->isScreenComplete($screen) ? 'complete' : 'active'
					]),
					$label
				);
			} else {
				$menu[] = $active ? '<span class="selected">' . $label . '</span>' : $label;
			}
		}

		return '<p>' . implode(" | " . PHP_EOL, $menu) . '</p>'
		 . '<style type="text/css">
		 	a { padding: 2px 4px; color: #FFF }
		 	.selected { font-weight: bold; text-decoration: none }
		 	.complete { background: green }
		 	.active { background: orange }
		 </style>';
	}

	/**
	 * Redirect to a screen, by its identifier
	 *
	 * @param  string  $identifier
	 * @return Illuminate\Http\RedirectResponse
	 */
	protected function redirectTo($identifier)
	{
		return $this->redirect->action($this->viewAction, $identifier);
	}

	/**
	 * Determine active screen, and return its identifier
	 *
	 * @return string
	 */
	protected function getActiveScreen()
	{
		return $this->screenIdentifiers[count($this->completedScreens)];
	}

	/**
	 * Get the next screen after the current screen $identifier, and return its
	 * identifier
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
