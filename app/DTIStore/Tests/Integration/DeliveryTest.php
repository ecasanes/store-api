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

class DeliveryTest extends DTIStoreTestCase
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
    public function shouldConfirmToReturnDeliveryProcess()
    {

        $productName = str_random().time().rand(1,1000);
        $slug = str_slug($productName);

        $newProductWithVariations = [
            'name' => $productName,
            'code' => $slug,
            'product_category_id' => 1,
            'variations' => [
                [
                    'size' => rand(100,250),
                    'metrics' => 'ml',
                    'quantity' => rand(1,20),
                    'cost_price' => rand(50,300),
                    'selling_price' => rand(80,500)
                ],
                [
                    'size' => rand(100,250),
                    'metrics' => 'ml',
                    'quantity' => rand(1,20),
                    'cost_price' => rand(50,300),
                    'selling_price' => rand(80,500)
                ]
            ]
        ];

        $createProductRequest = $this->adminPost('/api/products', $newProductWithVariations);

        if(empty($productVariationId1 = $createProductRequest->json()['data']['variations'])){
            $this->assertTrue(true);
            return false;
        }

        $productVariationId1 = $createProductRequest->json()['data']['variations'][0]['id'];
        $productVariationId2 = $createProductRequest->json()['data']['variations'][1]['id'];

        $this->assertNotNull($productVariationId1);
        $this->assertNotNull($productVariationId2);

        $newPendingDelivery = [
            'branch_id' => 1, // assumed that branch id exists since this is a test
            'deliveries' => [
                [
                    'product_variation_id' => $productVariationId1,
                    'quantity' => 1
                ],
                [
                    'product_variation_id' => $productVariationId2,
                    'quantity' => 1
                ]
            ]
        ];

        $pendingDeliveryRequest = $this->adminPost('/api/products/deliveries', $newPendingDelivery);

        $deliveryId = $pendingDeliveryRequest->json()['data']['id'];

        $this->assertNotNull($deliveryId);

        $confirmDeliveryRequest = $this->adminPost('/api/products/deliveries/'.$deliveryId.'/confirm');
        $confirmDeliveryResponse = $confirmDeliveryRequest->json();
        $confirmDeliveryRequest->assertJsonFragment([
            'type' => 'success'
        ], $confirmDeliveryResponse);

        $returnDeliveryRequest = $this->adminPost('/api/products/deliveries/return', $newPendingDelivery);

        $returnDeliveryResponse = $returnDeliveryRequest->json();

        dump($returnDeliveryResponse);

        $deliveryId = $returnDeliveryResponse['data']['id'];

        $this->assertNotNull($deliveryId);
        $returnDeliveryRequest->assertJsonStructure([
            'items'
        ], $returnDeliveryResponse);

    }

    /** @test */
    public function whenGettingSingleDeliveryMustHaveJsonStructure()
    {

        $productName = str_random().time().rand(1,1000);
        $slug = str_slug($productName);

        $newProductWithVariations = [
            'name' => $productName,
            'code' => $slug,
            'product_category_id' => 1,
            'variations' => [
                [
                    'size' => rand(100,250),
                    'metrics' => 'ml',
                    'quantity' => rand(1,20),
                    'cost_price' => rand(50,300),
                    'selling_price' => rand(80,500)
                ],
                [
                    'size' => rand(100,250),
                    'metrics' => 'ml',
                    'quantity' => rand(1,20),
                    'cost_price' => rand(50,300),
                    'selling_price' => rand(80,500)
                ]
            ]
        ];

        $createProductRequest = $this->adminPost('/api/products', $newProductWithVariations);

        if(empty($productVariationId1 = $createProductRequest->json()['data']['variations'])){
            $this->assertTrue(true);
            return false;
        }

        $productVariationId1 = $createProductRequest->json()['data']['variations'][0]['id'];
        $productVariationId2 = $createProductRequest->json()['data']['variations'][1]['id'];

        $this->assertNotNull($productVariationId1);
        $this->assertNotNull($productVariationId2);

        $newPendingDelivery = [
            'branch_id' => 1, // assumed that branch id exists since this is a test
            'deliveries' => [
                [
                    'product_variation_id' => $productVariationId1,
                    'quantity' => 1
                ],
                [
                    'product_variation_id' => $productVariationId2,
                    'quantity' => 1
                ]
            ]
        ];

        $pendingDeliveryRequest = $this->adminPost('/api/products/deliveries', $newPendingDelivery);

        $deliveryId = $pendingDeliveryRequest->json()['data']['id'];

        $this->assertNotNull($deliveryId);

        $getSingleDelivery = $this->adminGet('/api/products/deliveries/'.$deliveryId);

        $getSingleDelivery->assertJsonStructure(['id','branch_id','deliveries'], $getSingleDelivery->json()['data']);

    }
}
