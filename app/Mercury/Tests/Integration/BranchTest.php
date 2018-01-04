<?php

namespace Tests\Unit;

use App\Mercury\Tests\MercuryTestCase;
use App\Product;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BranchTest extends MercuryTestCase
{
    // when you insert to the db it will just rollback after test
    //use DatabaseTransactions;
    //use DatabaseMigrations;
    //use WithoutMiddleware;

    private $product;

    public function setUp()
    {
        parent::setUp();

    }

    /** @test */
    public function shouldHaveThisJsonStructureWhenThereIsAtLeastOneBranchAvailable()
    {

        $request = $this->adminGet('/api/branches');

        $response = $request->json();

        if(empty($response['data'])){
            return false;
        }

        $branch = $response['data'][0];

        $request->assertJsonStructure([
            'id',
            'name',
            'address',
            'city',
            'zip',
            'province',
            'phone',
            'status',
            'override_default_store_time',
            'default_start_time',
            'default_end_time',
            'company_id',
            'key',
            'firstname',
            'lastname',
            'email',
            'owner_user_id',
            'staff_count',
            //'manager_count'
        ], $branch);

        return false;

    }

    /** @test */
    public function singleBranchShouldHaveThisJsonStructure()
    {

        $request = $this->adminGet('/api/branches/1');

        $response = $request->json();

        $branch = $response['data'];

        $request->assertJsonStructure([
            'items',
        ], $branch);

        $item = $branch['items'][0];

        $request->assertJsonStructure([
            'id',
            'name',
            'code',
            'image_url',
            'product_category_id',
            'category',
            'category_code',
            'cost_price',
            'selling_price',
            'metrics',
            'size',
            'status',
            'product_id',
            'branch_quantity',
            'company_quantity',
            'last_delivery_quantity',
            'branch_total_delivery_quantity',
            'branch_delivery_percentage'
        ], $item);

        return false;

    }

    /** @test */
    public function shouldHaveThisJsonStructureWhenGettingSingleBranchInfo()
    {

        $request = $this->adminGet('/api/branches/1');

        $response = $request->json();

        if(empty($response['data'])){
            return false;
        }

        $branch = $response['data'];

        $request->assertJsonStructure([
            'id',
            'name',
            'address',
            'city',
            'zip',
            'province',
            'phone',
            'status',
            'override_default_store_time',
            'default_start_time',
            'default_end_time',
            'company_id',
            'key',
            'items'
        ], $branch);

        $items = $response['data']['items'];

        if(empty($items)){
            return false;
        }

        $item = $items[0];

        $request->assertJsonStructure([
            'id',
            'name',
            'code',
            'image_url',
            'product_category_id',
            'category',
            'category_code',
            'cost_price',
            'selling_price',
            'metrics',
            'size',
            'status',
            'product_id',
            'company_quantity',
            'branch_quantity',
            'last_delivery_quantity'
        ], $item);

        $request->assertJsonMissing(['quantity']);

        return false;

    }
}
