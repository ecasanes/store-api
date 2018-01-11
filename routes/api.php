<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


$baseNamespace = 'Api\Controllers\\';

$products = $baseNamespace . 'ProductController';
$orders = $baseNamespace . 'OrderController';
$variations = $baseNamespace . 'ProductVariationController';
$users = $baseNamespace . 'UserController';

/*
|--------------------------------------------------------------------------
| AUTH Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'auth'], function () use ($users) {

    Route::middleware(['auth.api'])->group(function () use ($users) {

        // POST - api/auth/login
        Route::post('login', $users . '@login');

    });

});


/*
|--------------------------------------------------------------------------
| PRODUCT Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'products', 'middleware' => 'auth.jwt'], function () use ($orders, $variations, $products) {

    // POST - api/products
    Route::post('', $products . '@create');

    Route::group(['prefix' => "{id}"], function () use ($variations, $products) {

        // PUT - api/products/{id}
        Route::put('', $products . '@update');

        // DELETE - api/products/{id}
        Route::delete('', $products . '@delete');

        Route::group(['prefix' => 'variations'], function () use ($variations) {

            // GET - api/products/{id}/variations
            Route::get('', $variations . '@getAllByProductId');

            // POST - api/products/{id}/variations
            Route::post('', $variations . '@createByProductId');

        });

    });

    Route::group(['prefix' => 'variations'], function () use ($products, $variations) {

        Route::group(['prefix' => '{id}'], function () use ($products, $variations) {

            // PUT - api/products/variations/{id}
            Route::put('', $variations . '@update');

        });


    });

    Route::group(['prefix' => 'categories'], function () use ($products) {

        // GET - api/products/categories
        Route::get('', $products . '@getAllCategories');

    });

    Route::group(['prefix' => 'conditions'], function () use ($products) {

        // GET - api/products/categories
        Route::get('', $products . '@getAllConditions');

    });

    Route::group(['prefix' => 'stocks'], function () use ($products) {

        // GET - api/products/stocks/stores/{id}
        Route::get('stores/{id}', $products . '@getStoreStocksById');

        // POST - api/products/stocks/stores/{id}
        Route::post('stores/{id}', $products . '@addStoreStocksById');

    });

    Route::group(['prefix' => 'wishlists'], function () use ($products) {

        // POST - api/products/wishlists
        Route::post('', $products . '@addToWishlist');

        // POST - api/products/wishlists/remove
        Route::post('remove', $products . '@removeInWishlist');

    });

    Route::group(['prefix' => 'carts'], function () use ($products) {

        // POST - api/products/carts/remove
        Route::post('remove', $products . '@removeInCart');

        // POST - api/products/carts
        Route::post('{userId}', $products . '@addToCart');

        // POST - api/products/carts/{userId}/count
        Route::get('{userId}/count', $products . '@getCartCountByUser');

    });

    Route::group(['prefix' => 'payments'], function () use ($products) {

        // POST - api/products/payments/methods
        Route::get('methods', $products . '@getAllPaymentMethods');

    });

    Route::group(['prefix' => 'vouchers'], function () use ($products) {

        // GET - api/products/vouchers
        Route::get('', $products . '@getAllVouchers');

        // GET - api/products/vouchers/validate
        Route::get('validate', $products . '@validateVoucherByCode');

        // POST - api/products/vouchers
        Route::post('', $products . '@createVoucher');

        // PUT - api/products/vouchers/{id}
        Route::put('{id}', $products . '@updateVoucher');

        // DELETE - api/products/vouchers/{id}
        Route::delete('{id}', $products . '@deleteVoucher');

    });


});

/*
|--------------------------------------------------------------------------
| ORDER Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'orders', 'middleware' => ['auth.jwt']], function () use ($orders) {

    // GET - api/orders
    Route::get('', $orders . '@getAllOrderHistory');

    // GET - api/orders/buyers/current
    Route::get('buyers/current', $orders . '@getCurrentBuyerOrderHistory');

    // GET - api/orders/sellers/current
    Route::get('sellers/current', $orders . '@getCurrentSellerOrderHistory');

    // POST - api/orders
    Route::post('', $orders . '@create');

    // PUT - api/orders/transactions/{id}/receive
    Route::put('transactions/{id}/receive', $orders . '@receiveTransactionById');

    // PUT - api/orders/transactions/{id}/ship
    Route::put('transactions/{id}/ship', $orders . '@shipTransactionById');

    // PUT - api/orders/transactions/{id}/receive-payment
    Route::put('transactions/{id}/receive-payment', $orders . '@receivePaymentTransactionById');

    // PUT - api/orders/transactions/{id}/complete
    Route::put('transactions/{id}/complete', $orders . '@completeTransactionById');

});


/*
|--------------------------------------------------------------------------
| USER Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'users', 'middleware' => 'auth.jwt'], function () use ($users) {

    // POST - api/users
    Route::post('', $users . '@create');

    // GET - api/users/current
    Route::get('current', $users . '@getCurrent');

    // GET - api/users
    Route::get('', $users . '@getAll');

    // GET - api/users/{id}
    Route::get('{id}', $users . '@get');

    // PUT - api/users{id}
    Route::put('{id}', $users . '@update');

    // DELETE - api/users/{id}
    Route::delete('{id}', $users . '@delete');

});


// POST - api/buyers/new
Route::post('buyers/new', $users . '@create');

// GET - api/products
Route::get('products', $products . '@getAll');

