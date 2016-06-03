<?php

namespace Bozboz\Ecommerce\Checkout;

interface Checkoutable
{

    /**
     * Lookup and return order
     *
     * @return
     */
    public function getCheckoutable();

    /**
     * Whether of not there is an order to return
     *
     * @return boolean
     */
    public function hasCheckoutable();

    /**
     * Set the current screen on the checkoutable instance
     *
     * @param $order Bozboz\Ecommerce\Orders\Order
     * @param $screenAlias string
     */
    public function markScreenAsComplete($order, $screenAlias);

    /**
     * Get the current screen the checkoutable instance is up to
     *
     * @param $order Bozboz\Ecommerce\Orders\Order
     * @return string
     */
    public function getCompletedScreen($order);
}