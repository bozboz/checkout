<?php

namespace Bozboz\Ecommerce\Checkout;

interface CheckoutableRepository
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
}