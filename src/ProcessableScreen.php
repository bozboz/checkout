<?php

namespace Bozboz\Ecommerce\Checkout;

abstract class ProcessableScreen extends Screen implements Processable
{
    public function getCheckoutable()
    {
        throw new CannotProcessException;
    }
}