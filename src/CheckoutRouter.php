<?php namespace Bozboz\Ecommerce\Checkout;

use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;

class CheckoutRouter
{
	/**
	 * @var Illuminate\Routing\Router
	 */
	protected $router;

	/**
	 * @var Illuminate\Foundation\Application
	 */
	protected $app;

	/**
	 * @var array
	 */
	protected $processes = [];

	/**
	 * @var string
	 */
	protected $controller = 'Bozboz\Ecommerce\Checkout\CheckoutController';

	/**
	 * @param Illuminate\Routing\Router  $router
	 * @param Illuminate\Foundation\Application  $app
	 */
	public function __construct(Router $router, Application $app)
	{
		$this->router = $router;
		$this->app = $app;
	}

	/**
	 * Register a new checkout process
	 *
	 * @param  string  $prefix
	 * @param  array  $params
	 * @return Bozboz\Ecommerce\Checkout\CheckoutProcess
	 */
	public function register($prefix, array $groupParams = [], array $config = [])
	{
		$groupParams['prefix'] = $prefix;

		$this->registerRoutes($groupParams, new Collection($config));

		$process = $this->app->make('Bozboz\Ecommerce\Checkout\CheckoutProcess');
		$process->setRouteAlias($prefix);

		return $this->processes[$prefix] = $process;
	}

	/**
	 * Get a registered process from a route
	 *
	 * @param  Illuminate\Routing\Route  $route
	 * @return Bozboz\Ecommerce\Checkout\CheckoutProcess
	 */
	public function getProcess(Route $route)
	{
		return $this->processes[$route->getPrefix()];
	}

	/**
	 * Register view/process routes for a process
	 *
	 * @param  array  $params
	 * @param  Illuminate\Support\Collection  $config
	 * @return void
	 */
	protected function registerRoutes($params, Collection $config)
	{
		$this->router->group($params, function() use ($params, $config)
		{
			$this->router->get('{screen?}', [
				'uses' => $config->get('view_action', $this->controller . '@view'),
				'as' => $params['prefix']
			]);

			$this->router->post('{screen?}', [
				'uses' => $config->get('process_action', $this->controller . '@process')
			]);
		});
	}
}