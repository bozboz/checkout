<?php

namespace Bozboz\Ecommerce\Checkout;

abstract class ProcessableScreen extends Screen implements Processable
{
    public function lookupOrder()
    {
        throw new CannotProcessException;
    }
}