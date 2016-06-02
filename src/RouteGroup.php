<?php

namespace Bozboz\Ecommerce\Checkout;

use Closure;

class RouteGroup
{

    /**
     * @var string
     */
    protected $defaultController = '\Bozboz\Ecommerce\Checkout\Http\Controllers\CheckoutController';

    public function __construct($process, $prefix, $router)
    {
        $this->process = $process;
        $this->prefix = $prefix;
        $this->router = $router;
    }

    public function register($groupParams, $closure)
    {
        $this->router->group($groupParams, function() use ($closure)
        {
            $closure($this);
        });
    }

    public function add($slug, $binding, $label = null, $params = [])
    {
        $params = $this->formParameters($params, ['view', 'process']);

        $alias =  $this->getScreenAlias($slug);

        $this->router->get($slug, [
            'uses' => $this->defaultController . '@view',
            'as' => $alias
        ] + $params['view']);

        $this->router->post($slug, [
            'uses' => $this->defaultController . '@process',
            'as' => $alias,
        ] + $params['process']);
debug([
    $params['process'],
    'DEBUG: /Z/finecut.jim/project/vendor/bozboz/checkout/src/RouteGroup.php:45',
]);
        $this->process->addScreen($alias, $binding, $label);
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
     * Get the alias for a screen, from its identifier
     *
     * @param  string  $identifier
     * @return string
     */
    protected function getScreenAlias($identifier)
    {
        $alias = [$this->prefix];

        if ($identifier !== '/') {
            $alias[] = $identifier;
        }

        return implode('.', $alias);
    }
}

