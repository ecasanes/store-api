<?php

namespace App\DTIStore\ServiceProviders;

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

        $interfacePath = 'App\DTIStore\Repositories';
        $repoPath = 'App\DTIStore\Repositories';

        $repositories = [

            'Product',
            'ProductVariation',
            'ProductCategory',

            'User',
            'Role',
            'UserRole',
            'UserStore',
            'Permission',
            'UserPermission',

            'Store',
            'StoreStock',

            'TransactionType',
            'Transaction',
            'TransactionItem',

            'Order',

        ];

        foreach($repositories as $repo){

            $interface = $interfacePath.'\\'.$repo.'Interface';
            $repository = $repoPath.'\\'.$repo.'Repository';

            $this->app->bind($interface, $repository);
        }

    }
}
