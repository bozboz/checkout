<?php

namespace Bozboz\Ecommerce\Checkout;

interface Checkoutable
{
    /**
     * Set the current screen on the checkoutable instance
     *
     * @param $order Bozboz\Ecommerce\Orders\Order
     * @param $screenAlias string
     */
    public function markScreenAsComplete($screenAlias);

    /**
     * Get the current screen the checkoutable instance is up to
     *
     * @param $order Bozboz\Ecommerce\Orders\Order
     * @return string
     */
    public function getCompletedScreen();

    /**
     * @return boolean Whether or not the order has any screens left to view/process
     */
    public function isComplete();
}