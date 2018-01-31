# Checkout

Define a series of interconnecting screens that plug together as part of a continuous process.

For examples of implementation look at these repos:

1. http://gitlab.lab/bozboz/finecut
2. http://gitlab.lab/bozboz/drusillas
3. http://gitlab.lab/bozboz/benton


## Setup

1. Require the package in Composer, by running `composer require bozboz/checkout`
2. Add the service provider in app/config/app.php

```
    Bozboz\Ecommerce\Checkout\Providers\CheckoutServiceProvider::class,
```
3. Add the `Checkout` facade to the aliases array in `in app/config/app.php`

```
    'Checkout' => Bozboz\Ecommerce\Checkout\Facades\Checkout::class,
```

## Usage

1. Register a new checkout process using the Checkout facade in `app/Http/routes.php`
2. Set a repository on the facade with the `using` method. The repository must implement the `Bozboz\Ecommerce\Checkout\CheckoutableRepository` interface and its purpose is to fetch the checkoutable instance. (The orders package has a default implementation to fetch the order instance from the session, `Bozboz\Ecommerce\Orders\OrderRepository`).
3. On the returned object, call `add` to add screens. The add method takes 4 parameters:
    1. The URL the screen will respond to
    2. The Screen class to use (resolved out the IoC container)
    3. An optional additional label to identify the screen, primarily used in the progress bar
    4. Route parameters (uses, as, before, etc.)


e.g.:

```php
<?php
Checkout::using('App\Ecommerce\Orders\OrderRepository')->register(['prefix' => 'checkout'], function($checkout)
{
    $checkout->add('/', 'App\Screens\Start', 'Start');
    $checkout->add('customer', 'App\Screens\CustomerDetails', 'Personal Info');
    $checkout->add('address', 'App\Screens\AddressSelection', 'Addresses');
    $checkout->add('delivery', 'App\Screens\ShippingSelection', 'Delivery');
    $checkout->add('billing', 'App\Screens\IframeBilling', 'Payment');
    $checkout->add('complete', 'App\Screens\OrderCompleted', 'Complete');
});
```

The above example will register the following URLs:

    GET  /checkout
    POST /checkout
    GET  /checkout/customer
    POST /checkout/customer
    GET  /checkout/address
    POST /checkout/address
    GET  /checkout/delivery
    POST /checkout/delivery
    GET  /checkout/billing
    POST /checkout/billing
    GET  /checkout/complete
    POST /checkout/complete

### Screens

Screens must extend `Bozboz\Ecommerce\Checkout\Screen` and must define a view() method.

Additionally, a `canSkip` method is supported, which must return a boolean. If this method returns `true`, the screen will be skipped in the process.


### Processing the screen

To define a processing action (hit when the screen URL is POSTed to) the screen class must implement `Bozboz\Ecommerce\Checkout\Processable`. This interface requires a `process()` method be defined.

Providing this method does not throw an instance of `Bozboz\Ecommerce\Checkout\ValidationException`, the checkout process will progress to the next screen upon completion.
