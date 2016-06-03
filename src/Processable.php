<?php

namespace Bozboz\Ecommerce\Checkout;

interface Processable
{
	public function process($order);

    public function getCheckoutable();
}
