<?php namespace Bozboz\Ecommerce\Checkout;

use Closure;

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
	 * @return void
	 */
	public function register(array $groupParams, Closure $closure)
	{
		$prefix = $groupParams['prefix'];

		$process = $this->app->make('Bozboz\Ecommerce\Checkout\CheckoutProcess');
		$process->setRouteAlias($prefix);

		$this->processes[$prefix] = $process;

		$this->router->group($groupParams, function() use ($closure, $process)
		{
			$closure($process);
		});
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
}
