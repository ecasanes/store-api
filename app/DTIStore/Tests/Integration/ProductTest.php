<?php

namespace Tests\Unit;

use App\DTIStore\Tests\DTIStoreTestCase;
use App\Product;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductTest extends DTIStoreTestCase
{
    // when you insert to the db it will just rollback after test
    //use DatabaseTransactions;
    //use DatabaseMigrations;
    //use WithoutMiddleware;

    private $product;

    public function setUp()
    {
        parent::setUp();

        $this->product = factory(Product::class)->create();

    }

    /** @test */
    public function hasRequiredFields()
    {
        $hasRequiredFields = false;

        if ($this->product->name != "" && $this->product->code != "") {
            $hasRequiredFields = true;
        }

        $this->assertTrue($hasRequiredFields, "Does not have required fields.");

    }

    /** @test */
    public function throwErrorResponseWhenNoRequiredFields()
    {

        $this->expectException(QueryException::class);

        $newProduct = factory(Product::class)->create(['name' => null]);

    }

    /** @test */
    public function confirmJsonStructureOnCreateProductWithEmptyBody()
    {

        $request = $this->adminPost('/api/products', []);
        $request->assertJsonStructure([
            'type',
            'message',
            'errors'
        ], $request->json());

        $request = $this->companyPost('/api/products', []);
        $request->assertJsonStructure([
            'type',
            'message',
            'errors'
        ], $request->json());

        $request = $this->companyStaffPost('/api/products', []);
        $request->assertJsonStructure([
            'type',
            'message',
            'errors'
        ], $request->json());

        $request = $this->companyStaffInventoryPost('/api/products', []);
        $request->assertJsonStructure([
            'type',
            'message',
            'errors'
        ], $request->json());

        $request = $this->companyStaffSalesPost('/api/products', []);
        $request->assertJsonStructure([
            'type',
            'message',
            'data'
        ], $request->json());
        $request->assertJsonMissing(['errors']);
    }

    /** @test */
    public function confirmJsonStructureOnGetProducts()
    {

        $request = $this->adminGet('/api/products');
        $request->assertJsonStructure([
            'type',
            'data',
            'length',
            'page',
            'limit'
        ], $request->json());
        $request->assertJsonMissing(['errors']);

        $request = $this->companyGet('/api/products');
        $request->assertJsonStructure([
            'type',
            'data',
            'length',
            'page',
            'limit'
        ], $request->json());
        $request->assertJsonMissing(['errors']);

        $request = $this->companyStaffGet('/api/products');
        $request->assertJsonStructure([
            'type',
            'data',
            'length',
            'page',
            'limit'
        ], $request->json());
        $request->assertJsonMissing(['errors']);

        $request = $this->companyStaffInventoryGet('/api/products');
        $request->assertJsonStructure([
            'type',
            'data',
            'length',
            'page',
            'limit'
        ], $request->json());
        $request->assertJsonMissing(['errors']);

        $request = $this->companyStaffSalesGet('/api/products');
        $request->assertJsonStructure([
            'type',
            'data',
            'length',
            'page',
            'limit'
        ], $request->json());
        $request->assertJsonMissing(['errors']);

    }

    /** @test */
    public function restockProductMustConfirmJsonStructure()
    {

        $productVariationId = 1;

        $requestGet = $this->adminGet('/api/products/variations/'.$productVariationId);

        $requestGet->assertJsonStructure(['total_branch_quantity', 'total_quantity'], $requestGet->json()['data']);

        $quantity = $requestGet->json()['data']['company_quantity'];

        $request = $this->adminPost('/api/products/variations/'.$productVariationId.'/stocks', [
            'quantity' => 1
        ]);

        dump($request->json());

        $request->assertJsonStructure(['quantity'], $request->json()['data']);

        $requestGet = $this->adminGet('/api/products/variations/'.$productVariationId);
        $newQuantity = $requestGet->json()['data']['company_quantity'];

        $this->assertEquals($newQuantity, $quantity + 1);

    }

    /** @test */
    public function subtractProductStocksMustConfirmJsonStructure()
    {

        $productVariationId = 1;

        $requestGet = $this->adminGet('/api/products/variations/'.$productVariationId);
        $quantity = $requestGet->json()['data']['company_quantity'];

        $request = $this->adminPost('/api/products/variations/'.$productVariationId.'/stocks/return', [
            'quantity' => 1
        ]);

        dump($request->json());

        $request->assertJsonStructure(['quantity'], $request->json()['data']);

        $requestGet = $this->adminGet('/api/products/variations/'.$productVariationId);
        $newQuantity = $requestGet->json()['data']['company_quantity'];

        $this->assertEquals($newQuantity, $quantity - 1);

    }

    /** @test */
    public function subtractProductStocksMustNotAllowIfNotEnough()
    {

        $productVariationId = 1;

        $requestGet = $this->adminGet('/api/products/variations/'.$productVariationId);
        $quantity = $requestGet->json()['data']['company_quantity'];

        $request = $this->adminPost('/api/products/variations/'.$productVariationId.'/stocks/return', [
            'quantity' => 100
        ]);

        $request->assertJsonFragment(['type'=>'error'], $request->json());

    }

    /** @test */
    public function getAllProductsShouldHaveThisJsonStructure()
    {

        $requestGet = $this->adminGet('/api/products');

        $singleProduct = $requestGet->json()['data'][0];

        $requestGet->assertJsonStructure([
            'id',
            'product_id',
            'name',
            'code',
            'image_url',
            'category',
            'category_code',
            'product_category_id',
            'product_variation_id',
            'size',
            'metrics',
            'cost_price',
            'selling_price',
            'company_quantity',
            'branch_quantity',
            'branch_id',
            'total_branch_quantity',
            'total_quantity'
        ], $singleProduct);

    }
}
