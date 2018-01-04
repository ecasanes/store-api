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

// consists of Product, ProductVariation, ProductCategories
$products = $baseNamespace . 'ProductController';
$transactions = $baseNamespace . 'TransactionController';
$variations = $baseNamespace . 'ProductVariationController';
$categories = $baseNamespace . 'ProductCategoryController';
$companies = $baseNamespace . 'CompanyController';
$branches = $baseNamespace . 'BranchController';
$users = $baseNamespace . 'UserController';
$test = $baseNamespace . 'TestController';

$deliveries = $baseNamespace . 'DeliveryController';
$deliveryItems = $baseNamespace . 'DeliveryItemController';
$activityLogs = $baseNamespace . 'ActivityLogController';
//$activityLogTypes = $baseNamespace . 'ActivityLogTypeController';
$priceRule = $baseNamespace . 'PriceRuleController';

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

    Route::middleware(['auth.api.expired'])->group(function () use ($users) {

        // POST - api/auth/mobile/refresh
        Route::post('mobile/refresh', $users . '@refreshToken');

    });

    Route::group(['prefix' => 'mobile'], function () use ($users) {

        Route::middleware(['auth.api.mobile'])->group(function () use ($users) {

            // POST - api/auth/mobile/login
            Route::post('login', $users . '@login');

        });

    });

});



/*
|--------------------------------------------------------------------------
| Inventory Tracking routes - POST
|--------------------------------------------------------------------------
*/

Route::middleware(['pos.post'])->group(function () use ($products, $users, $transactions) {

    Route::group(['prefix' => 'tracking', 'middleware' => 'staff'], function () use ($products, $users, $transactions) {

        Route::group(['prefix' => 'transactions'], function () use ($transactions) {

            Route::group(['prefix' => 'products'], function () use ($transactions) {

                // POST - api/tracking/transactions/products/short
                Route::post('short', $transactions . '@adjustmentShortTransactionByBranchKey');

                // POST - api/tracking/transactions/products/short/{id}/void
                Route::post('short/{id}/void', $transactions . '@voidAdjustmentShortTransactionByBranchKey');

                // POST - api/tracking/transactions/products/shortover
                Route::post('shortover', $transactions . '@adjustmentShortOverTransactionByBranchKey');

                // POST - api/tracking/transactions/products/shortover/{id}/void
                Route::post('shortover/{id}/void', $transactions . '@voidAdjustmentShortOverTransactionByBranchKey');

            });

            Route::group(['prefix' => 'sale'], function () use ($transactions) {

                // POST - api/tracking/transactions/sale
                Route::post('', $transactions . '@createSaleTransactionByBranchKey');

                // POST - api/tracking/transactions/sale/return
                Route::post('return', $transactions . '@returnSaleTransactionByBranchKey');

                // POST - api/tracking/transactions/sale/short
                Route::post('short', $transactions . '@shortSaleTransactionByBranchKey');

                // POST - api/tracking/transactions/sale/shortover
                Route::post('shortover', $transactions . '@shortoverSaleTransactionByBranchKey');

                // POST - api/tracking/transactions/sale/return/{id}/void
                Route::post('return/{id}/void', $transactions . '@voidReturnSaleTransactionByBranchKey');

                // POST - api/tracking/transactions/sale/short/{id}/void
                Route::post('short/{id}/void', $transactions . '@voidShortSaleTransactionByBranchKey');

                // POST - api/tracking/transactions/sale/shortover/{id}/void
                Route::post('shortover/{id}/void', $transactions . '@voidShortoverSaleTransactionByBranchKey');

            });

            // POST - api/tracking/transactions/{id}/void
            Route::post('{id}/void', $transactions . '@voidTransactionByBranchKey');

        });

        // POST - api/tracking/users/customers
        Route::post('users/customers', $users . '@createCustomerFromBranchStaff');

    });

});


/*
|--------------------------------------------------------------------------
| Inventory Tracking routes - VIEW
|--------------------------------------------------------------------------
*/

Route::middleware(['pos.view'])->group(function () use ($users, $categories, $products, $transactions, $branches) {

    Route::group(['prefix' => 'tracking'], function () use ($users, $categories, $products, $transactions, $branches) {

        Route::group(['prefix' => 'transactions'], function () use ($transactions) {

            // POST - api/tracking/transactions
            Route::post('', $transactions. '@uploadNewTransaction');

            // GET - api/tracking/transactions
            Route::get('', $transactions . '@getByBranchKey');

            // GET - api/tracking/transactions/{id}
            Route::get('{id}', $transactions . '@find');

            // GET - api/tracking/transactions/{id}/products
            Route::get('{id}/products', $transactions . '@getProductsByTransactionId');

            // GET - api/tracking/transactions/{id}/categories
            Route::get('{id}/products/categories', $transactions . '@getProductCategoriesByTransactionId');

            // GET - api/tracking/transactions/receipts/{orNo}
            Route::get('receipts/{orNo}', $transactions . '@findByOr');

            // POST - api/tracking/transactions/discounts/calculate
            Route::post('discounts/calculate', $transactions . '@calculateDiscountFromBranch');

        });

        Route::group(['prefix' => 'products'], function() use ($categories, $products) {

            // GET - api/tracking/products/discounts/special
            Route::get('discounts/special', $products . '@getSpecialDiscounts');

            // GET - api/tracking/products
            Route::get('', $products . '@getByBranchKey');

            // GET - api/tracking/products/vat
            Route::get('vat', $products . '@getVatByBranchKey');

            // GET - api/tracking/products/threshold
            Route::get('threshold', $products . '@getLowInventoryThresholdByBranchKey');

            // GET - api/tracking/products/categories
            Route::get('categories', $categories . '@getAll');

        });

        Route::group(['prefix' => 'users'], function() use ($users) {

            // GET - api/tracking/users/staff/{staffId}
            Route::get('staff/{staffId}', $users . '@getByStaffId');

            // GET - api/tracking/users/customers
            Route::get('customers', $users . '@getCustomers');

            // GET - api/tracking/users/customers/{customerId}
            Route::get('customers/{customerId}', $users . '@getByCustomerId');

            // POST - api/tracking/users/guests
            Route::post('guests', $users . '@createGuestFromBranch');

        });

    });

});

Route::middleware(['pos.public'])->group(function () use ($transactions, $products, $users, $branches){

    Route::group(['prefix' => 'tracking/sync'], function() use ($transactions, $products, $users, $branches) {

        // GET - api/tracking/sync/branches
        Route::get('branches', $branches . '@getAll');

        // GET - api/tracking/sync/branches/{branchKey}
        Route::get('branches/key/{branchKey}', $branches . '@getBranchSalesSummaryByKey');

        // GET - api/tracking/sync/users
        Route::get('users', $users . '@getAll');

        // POST - api/tracking/sync/users/customers/new
        Route::post('users/customers/new', $users . '@createNewCustomers');

        // GET - api/tracking/sync/products/price-rules
        Route::get('products/price-rules', $products . '@getAllPriceRules');

        // GET - api/tracking/sync/transactions/types
        Route::get('transactions/types',  $transactions . '@getAllTransactionTypes');

        // GET - api/tracking/sync/transactions/or-numbers/{branchId}
        Route::get('transactions/or-numbers/{branchId}', $transactions. '@getAllOrNumbersByBranchId');

    });

});

// temporary api
Route::group(['prefix' => 'tracking'], function() use ($users, $branches) {

    // GET - api/tracking/branches
    Route::get('branches', $branches . '@getAll');

    // GET - api/tracking/branches/{branchKey}
    Route::get('branches/key/{branchKey}', $branches . '@getBranchSalesSummaryByKey');

});


/*
|--------------------------------------------------------------------------
| PRODUCT Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'products', 'middleware' => 'auth.jwt'], function () use ($transactions, $categories, $variations, $products) {

    // GET - api/products
    Route::get('', $products . '@getAll');

    // POST - api/products/export
    Route::post('export', $products . '@export');

    // checks if role is company staff then it must have an inventory permission
    Route::middleware('company.staff.inventory')->group(function () use ($transactions, $categories, $variations, $products) {

        // POST - api/products
        Route::post('', $products . '@create');

        Route::group(['prefix' => "{id}"], function () use ($variations, $products) {

            // PUT - api/products/{id}
            Route::put('', $products . '@update');

            // DELETE - api/products/{id}
            Route::delete('', $products . '@delete');

            // POST - api/products/{id}/upload
            Route::post('upload', $products . '@uploadImage');

            Route::group(['prefix' => 'variations'], function () use ($variations) {

                // GET - api/products/{id}/variations
                Route::get('', $variations . '@getAllByProductId');

                // POST - api/products/{id}/variations
                Route::post('', $variations . '@createByProductId');

            });

            Route::group(['prefix' => 'stocks'], function () use ($products) {

                // POST - api/products/{id}/stocks
                Route::post('', $products . '@addStocksByProductId');

                // DELETE - api/products/{id}/stocks
                Route::delete('', $products . '@subtractStocksByProductId');

            });

        });

        Route::group(['prefix' => 'variations'], function () use ($products, $variations) {

            // GET - api/products/variations
            Route::get('', $variations . '@getAll');

            // POST - api/products/variations
            Route::post('', $variations . '@create');

            Route::group(['prefix' => '{id}'], function () use ($products, $variations) {

                // GET - api/products/variations/{id}
                Route::get('', $variations . '@get');

                // PUT - api/products/variations/{id}
                Route::put('', $variations . '@update');

                // DELETE - api/products/variations/{id}
                Route::delete('', $variations . '@delete');

                Route::group(['prefix' => 'stocks'], function () use ($products) {

                    // POST - api/products/variations/{id}/stocks
                    Route::post('', $products . '@addStocksByVariationId');

                    // POST - api/products/variations/{id}/stocks/return
                    Route::post('return', $products . '@subtractStocksByVariationId');

                });

            });


        });

        Route::group(['prefix' => 'categories'], function () use ($categories) {

            // GET - api/products/categories
            Route::get('', $categories . '@getAll');

            // POST - api/products/categories
            Route::post('', $categories . '@create');

            // GET - api/products/categories/{id}
            Route::get('{id}', $categories . '@get');

            // DELETE - api/products/categories/{id}
            Route::delete('{id}', $categories . '@delete');

            // PUT - api/products/categories/{id}
            Route::put('{id}', $categories . '@update');

            // POST - api/products/categories/export
            Route::post('export', $categories . '@export');

        });

        Route::group(['prefix' => 'deliveries'], function () use ($products) {

            // POST - api/products/deliveries
            Route::post('', $products . '@createPendingDeliveries');

            // POST - api/products/deliveries/confirm
            Route::post('confirm', $products . '@createConfirmedDeliveries');

            // POST - api/products/deliveries/return
            Route::post('return', $products . '@returnBranchStocksToCompany');

            // GET - api/products/deliveries/{id}
            Route::get('{id}', $products . '@getDeliveryWithItems');

            // PUT - api/products/deliveries/{id}
            Route::put('{id}', $products . '@updatePendingDeliveries');

            // POST - api/products/deliveries/{id}/confirm
            Route::post('{id}/confirm', $products . '@confirmPendingDeliveriesById');

            // POST - api/products/deliveries/{id}/confirm
            Route::post('{id}/void', $products . '@voidPendingDeliveryById');


        });

        Route::group(['prefix' => 'alerts'], function () use ($products) {

            // GET - api/products/alerts
            Route::get('', $products . '@getAlerts');


        });

        Route::group(['prefix' => 'stocks'], function () use ($products) {

            // GET - api/products/stocks
            Route::get('', $products . '@getCompanyStocks');

            // GET - api/products/stocks/branches/{id}
            Route::get('branches/{id}', $products . '@getBranchStocksById');
        });

        Route::group(['prefix' => 'transactions'], function () use ($transactions) {

            // GET - api/products/transactions/ledger/warehouse
            Route::get('ledger/warehouse', $transactions . '@getWarehouseLedger');

        });

    });

    // checks if role is company staff then it must have an sales permission
    Route::middleware('company.staff.sale')->group(function () use ($transactions) {

        Route::group(['prefix' => 'transactions'], function () use ($transactions) {

            // GET - api/products/transactions/ledger/warehouse
            Route::get('ledger/warehouse', $transactions . '@getWarehouseLedger');

            // GET - api/products/transactions/ledger/branch
            Route::get('ledger/branch', $transactions . '@getBranchLedger');

            // GET - api/products/transactions/sales
            Route::get('sales', $transactions . '@getBranchesSalesSummary');

            // GET - api/products/transactions/sales/total
            Route::get('sales/total', $transactions . '@getTotalSales');

            // GET - api/products/transactions/sales/top
            Route::get('sales/top', $transactions . '@getTopSaleItems');

            // GET - api/products/transactions/sales/summary
            Route::get('sales/summary', $transactions . '@getSalesSummary');

            // GET - api/products/transactions/sales/summary/changes
            Route::get('sales/summary/changes', $transactions . '@getSalesChanges');

            // GET - api/products/transactions/sales/summary/daily
            Route::get('sales/summary/daily', $transactions . '@getDailySales');

            // GET - api/products/transactions/sales/items/summary
            Route::get('sales/items/summary', $transactions . '@getAllProductsSalesSummary');

            // GET - api/products/transactions/sales/{orNumber}
            Route::get('sales/{orNumber}', $transactions . '@findByOr');

            // GET - api/products/transactions/{id}
            Route::get('{id}', $transactions . '@find');

            // POST - api/products/transactions/{id}/void
            Route::post('{id}/void', $transactions . '@voidTransactionsById');


        });

    });


});

/*
|--------------------------------------------------------------------------
| TRANSACTION Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'transactions', 'middleware' => ['auth.jwt']], function () use ($transactions) {

    // GET - api/transactions
    Route::get('', $transactions . '@getAll');

    // POST - api/transactions/sale
    Route::post('sale', $transactions . '@createSaleTransaction');

    // POST - api/transactions/export
    Route::post('export', $transactions . '@export');

    // GET - api/transactions/types
    Route::get('types', $transactions . '@getAllTransactionTypes');

});

/*
|--------------------------------------------------------------------------
| DELIVERY Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'deliveries', 'middleware' => 'auth.jwt'], function () use ($deliveries, $deliveryItems) {

    // GET - api/deliveries
    Route::get('', $deliveries . '@getAll');

    // POST - api/deliveries
    Route::post('', $deliveries . '@create');

    // POST - api/deliveries/export
    Route::post('export', $deliveries . '@export');

    Route::group(['prefix' => "{id}"], function () use ($deliveries, $deliveryItems) {

        // GET - api/deliveries/{id}
        Route::get('', $deliveries . '@get');

        // GET - api/deliveries/{id}/items
        Route::get('items', $deliveryItems . '@getByDeliveryId');

        // POST - api/deliveries/{id}/items
        Route::post('items', $deliveryItems . '@create');

        // PUT - api/deliveries/{id}
        Route::put('', $deliveries . '@update');

        // DELETE - api/deliveries/{id}
        Route::delete('', $deliveries . '@delete');

    });

    Route::group(['prefix' => 'items'], function () use ($deliveryItems) {

        // GET - api/deliveries/items
        Route::get('', $deliveryItems . '@getAll');

        // GET - api/deliveries/items/{id}
        Route::get('{id}', $deliveryItems . '@get');

        // PUT - api/deliveries/items/{id}
        Route::put('{id}', $deliveryItems . '@update');

        // DELETE - api/deliveries/items/{id}
        Route::delete('{id}', $deliveryItems . '@delete');
    });

});

/*
|--------------------------------------------------------------------------
| COMPANY Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'companies', 'middleware' => ['auth.jwt']], function () use ($companies) {

    Route::middleware('admin')->group(function () use ($companies) {

        // GET - api/companies
        Route::get('', $companies . '@getAll');

        // DELETE - api/companies/{id}
        Route::delete('{id}', $companies . '@delete');

    });

    Route::middleware('company')->group(function () use ($companies) {

        // PUT - api/companies/{id}
        Route::put('{id}', $companies . '@update');

    });


});


/*
|--------------------------------------------------------------------------
| BRANCH Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'branches', 'middleware' => ['auth.jwt']], function () use ($branches) {

    // GET - api/branches
    Route::get('', $branches . '@getAll');

    // POST - api/branches
    Route::post('', $branches . '@create');

    // GET - api/branches/{id}
    Route::get('{id}', $branches . '@get');

    // PUT - api/branches/{id}
    Route::put('{id}', $branches . '@update');

    // DELETE - api/branches/{id}
    Route::delete('{id}', $branches . '@delete');

    // GET - api/branches/key/{key}
    Route::get('key/{key}', $branches . '@findByBranchKey');

});

/*
|--------------------------------------------------------------------------
| ACTIVITY LOG Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'logs', 'middleware' => 'auth.jwt'], function () use ($activityLogs) {

    // GET - api/logs
    Route::get('', $activityLogs . '@getAll');

    // POST - api/logs
    Route::post('', $activityLogs . '@create');

    Route::group(['prefix' => 'types', 'middleware' => 'auth.jwt'], function () use ($activityLogs) {

        // GET - api/logs/types
        Route::get('', $activityLogs . '@getAllActivityLogTypes');

    });

});

/*
|--------------------------------------------------------------------------
| USER Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'users', 'middleware' => 'auth.jwt'], function () use ($users) {

    // POST - api/users
    Route::post('', $users . '@create');

    // GET - api/users
    Route::get('', $users . '@getAll');

    // GET - api/users/count
    Route::get('count', $users . '@getCountByFilter');

    // GET - api/users/{id}
    Route::get('{id}', $users . '@get');

    // GET - api/users/customers/{memberId}
    Route::get('customers/{memberId}', $users . '@getByCustomerId');

    // GET - api/users/permissions
    Route::get('permissions/{id}', $users . '@getCompanyPermissions');

    // PUT - api/users{id}
    Route::put('{id}', $users . '@update');

    // DELETE - api/users/{id}
    Route::delete('{id}', $users . '@delete');

    // POST - api/users/export
    Route::post('export', $users . '@export');

    // POST - api/users/email
    Route::post('email', $users . '@getFranchiseeByEmail');

});

/*
|--------------------------------------------------------------------------
| PRICE RULE Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'pricing', 'middleware' => 'auth.jwt'], function () use ($priceRule) {

    // GET - api/pricing
    Route::get('', $priceRule . '@getAll');

    // GET - api/pricing/id
    Route::get('{id}', $priceRule . '@get');

    // GET - api/pricing/code/check
    Route::get('{code}/check', $priceRule . '@checkIfActive');

    // POST - api/pricing
    Route::post('', $priceRule . '@create');

    // PUT - api/pricing/id
    Route::put('{id}', $priceRule . '@update');

    // DELETE - api/pricing
    Route::delete('{id}', $priceRule . '@delete');

    // POST - api/pricing/export
    Route::post('export', $priceRule . '@export');

});

/*
|--------------------------------------------------------------------------
| TEST Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'test'], function () use ($test) {

    // GET - api/test
    Route::get('', $test . '@index');

});

