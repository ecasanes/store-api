<?php

namespace App\Mercury\ServiceProviders;

use Illuminate\Support\ServiceProvider;

class BackendServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

        $interfacePath = 'App\Mercury\Repositories';
        $repoPath = 'App\Mercury\Repositories';

        $repositories = [

            'Product',
            'ProductVariation',
            'ProductCategory',

            'User',
            'Role',
            'UserRole',
            'Permission',
            'UserPermission',

            'Company',

            'BranchStaff',
            'Branch',

            'CustomerUser',

            'CompanyStock',
            'BranchStock',

            'Delivery',
            'DeliveryItem',

            'ActivityLog',
            'ActivityLogType',

            'PriceRule',

            'TransactionType',
            'Transaction',
            'TransactionItem',

            'Export'

        ];

        foreach($repositories as $repo){

            $interface = $interfacePath.'\\'.$repo.'Interface';
            $repository = $repoPath.'\\'.$repo.'Repository';

            $this->app->bind($interface, $repository);
        }

    }
}
