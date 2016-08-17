<?php

namespace Bozboz\Ecommerce\Checkout;

use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Closure;

class CheckoutRouter
{
	/**
	 * @var Illuminate\Contracts\Container\Container
	 */
	protected $container;

	/**
	 * @var array
	 */
	protected $processes = [];

	/**
	 * @var Bozboz\Ecommerce\Orders\OrderRepository
	 */
	protected $repo;

	/**
	 * @param Illuminate\Contracts\Container\Container  $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	public function using($repo)
	{
		$this->repo = $repo;
		return $this;
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

        $process = $this->container->make('checkout.process');

        $process->setRepository(
            $this->container->make($this->repo)
        );

        $this->processes[$prefix] = $process;

        $routeGroup = new RouteGroup($process, $prefix, $this->container->make('router'));
        $routeGroup->register($groupParams, $closure);
    }

	/**
	 * Get a registered process from a route
	 *
	 * @param  Illuminate\Routing\Route  $route
	 * @return Bozboz\Ecommerce\Checkout\CheckoutProcess
	 */
	public function getProcess(Route $route)
	{
        $prefixParts = explode('/', $route->getPrefix());
		return $this->processes[end($prefixParts)];
	}
}
