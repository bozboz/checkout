# Checkout

Define a series of interconnecting screens that plug together as part of a continuous process.


## Setup

	1) Add the `Bozboz\Ecommerce\Checkout\CheckoutServiceProvider` service provider in app/config/app.php
	2) Add the `Checkout` facade (`Bozboz\Ecommerce\Checkout\Facades\Checkout`) to the aliases array in `in app/config/app.php


## Usage

	1) Register a new checkout process using the Checkout facade
	2) On the returned object, call `add` to add screens. The add method takes 4 parameters:
		a) The URL the screen will respond to
		b) The Screen class to use (resolved out the IoC container)
		c) An optional additional label to identify the screen
		d) Route parameters (uses, as, before, etc.)


For example:

```
$checkout = Checkout::register(['prefix' => 'checkout'], function($checkout)
{
	$checkout->add('/', 'LoginOrRegister', 'Start');
	$checkout->add('customer', 'CustomerDetails', 'Personal Info');
	$checkout->add('address', 'AddressSelection', 'Addresses');
	$checkout->add('billing', 'IframeBilling', 'Payment');
	$checkout->add('complete', 'OrderCompleted');
});
```

The above example will register the following URLs:

	GET /checkout
	POST /checkout
	GET /checkout/customer
	POST /checkout/customer
	GET /checkout/address
	POST /checkout/address
	GET /checkout/billing
	POST /checkout/billing
	GET /checkout/complete
	POST /checkout/complete

### Screens

Screens must extend `Bozboz\Ecommerce\Checkout\Screen` and must define a view() method.

Additionally, a `canSkip` method is supported, which must return a boolean. If this method returns `true`, the screen will be skipped in the process.


### Processing the screen

To define a processing action on the screen (hit when the screen URL is POSTed to, the screen class must implement `Bozboz\Ecommerce\Checkout\Processable`. This interface requires a process() method be defined.

Providing this method does not throw an instance of `Bozboz\Ecommerce\Checkout\ValidationException`, the checkout process will progress to the next screen upon completion.
