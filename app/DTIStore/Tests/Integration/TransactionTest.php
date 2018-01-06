<?php

namespace Tests\Unit;

use App\BranchStock;
use App\DTIStore\Tests\DTIStoreTestCase;
use App\Product;
use App\ProductVariation;
use App\Transaction;
use App\TransactionItem;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionTest extends DTIStoreTestCase
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
    public function mustBeAbleToProduceSameStocksFromMultipleTransactions()
    {

        //assumes that there is product variation 1 and 2 in branch_stocks
        //assumes empty transaction and transaction items

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();
        $productB = BranchStock::where('product_variation_id',2)->where('branch_id',1)->first();

        DB::statement("SET FOREIGN_KEY_CHECKS=0");
        Transaction::truncate();
        TransactionItem::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $initialProductAStocks = 50;
        $initialProductBStocks = 20;

        $productA->update([
            'quantity' => $initialProductAStocks
        ]);

        $productB->update([
            'quantity' => $initialProductBStocks
        ]);


        $saleTransaction = $this->standardStaffPost('/api/tracking/transactions/sale', [
            'or_no' => 11111111,
            'items' => [
                [
                    'product_variation_id' => 1,
                    'quantity' => 5
                ],
                [
                    'product_variation_id' => 2,
                    'quantity' => 10
                ]
            ],
            'customer_id' => $this->getCustomerId()
        ]);

        $saleId = $saleTransaction->json()['data']['transaction']['id'];

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();
        $productB = BranchStock::where('product_variation_id',2)->where('branch_id',1)->first();

        $this->assertEquals(45, $productA->quantity, "Expects stocks to be 45 as it is subtracted by 5");
        $this->assertEquals(10, $productB->quantity, "Expects stocks to be 10 as it is subtracted by 10");

        // DO RETURN SALE - VOID RETURN SALE FIRST TIME

        $returnSaleTransaction = $this->coordinatorStaffPost('/api/tracking/transactions/sale/return', [
            'or_no' => 11111111,
            'items' => [
                [
                    'product_variation_id' => 1,
                    'quantity' => 2
                ],
                [
                    'product_variation_id' => 2,
                    'quantity' => 3
                ]
            ]
        ]);

        $returnSaleId = $returnSaleTransaction->json()['data']['transaction']['id'];

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();
        $productB = BranchStock::where('product_variation_id',2)->where('branch_id',1)->first();

        $this->assertEquals(47, $productA->quantity, "Expects stocks to be 47 as it is added by 2");
        $this->assertEquals(13, $productB->quantity, "Expects stocks to be 13 as it is added by 3");

        $voidReturnSaleTransaction = $this->coordinatorStaffPost('/api/tracking/transactions/sale/return/'.$returnSaleId.'/void', []);

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();
        $productB = BranchStock::where('product_variation_id',2)->where('branch_id',1)->first();

        $this->assertEquals(45, $productA->quantity, "Expects stocks to be 47 as it is subtracted by 2");
        $this->assertEquals(10, $productB->quantity, "Expects stocks to be 13 as it is subtracted by 3");

        //DO RETURN SALE - VOID RETURN SALE ANOTHER TIME

        $returnSaleTransaction = $this->coordinatorStaffPost('/api/tracking/transactions/sale/return', [
            'or_no' => 11111111,
            'items' => [
                [
                    'product_variation_id' => 1,
                    'quantity' => 2
                ],
                [
                    'product_variation_id' => 2,
                    'quantity' => 3
                ]
            ]
        ]);

        $returnSaleId = $returnSaleTransaction->json()['data']['transaction']['id'];

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();
        $productB = BranchStock::where('product_variation_id',2)->where('branch_id',1)->first();

        $this->assertEquals(47, $productA->quantity, "Expects stocks to be 47 as it is added by 2");
        $this->assertEquals(13, $productB->quantity, "Expects stocks to be 13 as it is added by 3");

        $voidReturnSaleTransaction = $this->coordinatorStaffPost('/api/tracking/transactions/sale/return/'.$returnSaleId.'/void', []);

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();
        $productB = BranchStock::where('product_variation_id',2)->where('branch_id',1)->first();

        $this->assertEquals(45, $productA->quantity, "Expects stocks to be 47 as it is subtracted by 2");
        $this->assertEquals(10, $productB->quantity, "Expects stocks to be 13 as it is subtracted by 3");

        //FINALLY VOID TRANSACTION

        $voidSaleTransaction = $this->coordinatorStaffPost('/api/tracking/transactions/'.$saleId.'/void', []);

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();
        $productB = BranchStock::where('product_variation_id',2)->where('branch_id',1)->first();

        $this->assertEquals(50, $productA->quantity, "Expects stocks to be 50 as it is added by 5");
        $this->assertEquals(20, $productB->quantity, "Expects stocks to be 20 as it is added by 10");



    }

    /** @test */
    public function mustBeAbleToProduceSameStocksWhenSaleIsCancelledWithReturns()
    {

        //assumes that there is product variation 1 and 2 in branch_stocks
        //assumes empty transaction and transaction items

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();
        $productB = BranchStock::where('product_variation_id',2)->where('branch_id',1)->first();

        DB::statement("SET FOREIGN_KEY_CHECKS=0");
        Transaction::truncate();
        TransactionItem::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $initialProductAStocks = 50;
        $initialProductBStocks = 20;

        $productA->update([
            'quantity' => $initialProductAStocks
        ]);

        $productB->update([
            'quantity' => $initialProductBStocks
        ]);


        $saleTransaction = $this->standardStaffPost('/api/tracking/transactions/sale', [
            'or_no' => 11111111,
            'items' => [
                [
                    'product_variation_id' => 1,
                    'quantity' => 5
                ],
                [
                    'product_variation_id' => 2,
                    'quantity' => 10
                ]
            ],
            'customer_id' => $this->getCustomerId()
        ]);

        $saleId = $saleTransaction->json()['data']['transaction']['id'];

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();
        $productB = BranchStock::where('product_variation_id',2)->where('branch_id',1)->first();

        $this->assertEquals(45, $productA->quantity, "Expects stocks to be 45 as it is subtracted by 5");
        $this->assertEquals(10, $productB->quantity, "Expects stocks to be 10 as it is subtracted by 10");

        // DO RETURN SALE - VOID RETURN SALE FIRST TIME

        $returnSaleTransaction = $this->coordinatorStaffPost('/api/tracking/transactions/sale/return', [
            'or_no' => 11111111,
            'items' => [
                [
                    'product_variation_id' => 1,
                    'quantity' => 2
                ],
                [
                    'product_variation_id' => 2,
                    'quantity' => 3
                ]
            ]
        ]);

        $returnSaleId = $returnSaleTransaction->json()['data']['transaction']['id'];

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();
        $productB = BranchStock::where('product_variation_id',2)->where('branch_id',1)->first();

        $this->assertEquals(47, $productA->quantity, "Expects stocks to be 47 as it is added by 2");
        $this->assertEquals(13, $productB->quantity, "Expects stocks to be 13 as it is added by 3");

        //FINALLY VOID TRANSACTION

        $voidSaleTransaction = $this->coordinatorStaffPost('/api/tracking/transactions/'.$saleId.'/void', []);

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();
        $productB = BranchStock::where('product_variation_id',2)->where('branch_id',1)->first();

        $this->assertEquals(50, $productA->quantity, "Expects stocks to be 50 as it is added by 5");
        $this->assertEquals(20, $productB->quantity, "Expects stocks to be 20 as it is added by 10");



    }

    /** @test */
    public function mustHaveSameItemQuantityForShortAndVoidShortTransaction()
    {

        //assumes that there is product variation 1 in branch_stocks
        //assumes empty transaction and transaction items

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();

        DB::statement("SET FOREIGN_KEY_CHECKS=0");
        Transaction::truncate();
        TransactionItem::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $initialProductAStocks = 50;
        //$initialProductBStocks = 20;

        $productA->update([
            'quantity' => $initialProductAStocks
        ]);


        $shortTransaction = $this->coordinatorStaffPost('/api/tracking/transactions/products/short', [
            'items' => [
                [
                    'product_variation_id' => 1,
                    'quantity' => 10
                ]
            ],
            'remarks' => 'Short Testing'
        ]);

        $shortTransactionId = $shortTransaction->json()['data']['transaction']['id'];

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();

        $this->assertEquals(40, $productA->quantity, "Expects stocks to be 40 as it is subtracted by 10");

        //FINALLY VOID SHORT

        $voidShortTransaction = $this->coordinatorStaffPost('/api/tracking/transactions/products/short/'.$shortTransactionId.'/void', []);

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();

        $this->assertEquals(50, $productA->quantity, "Expects stocks to be 50 as it is added by 10");



    }

    /** @test */
    public function mustHaveSameItemQuantityForShortoverAndVoidShortoverTransaction()
    {

        //assumes that there is product variation 1 and 2 in branch_stocks
        //assumes empty transaction and transaction items

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();
        $productB = BranchStock::where('product_variation_id',2)->where('branch_id',1)->first();
        $productC = BranchStock::where('product_variation_id',3)->where('branch_id',1)->first();
        $productD = BranchStock::where('product_variation_id',4)->where('branch_id',1)->first();

        DB::statement("SET FOREIGN_KEY_CHECKS=0");
        Transaction::truncate();
        TransactionItem::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $initialProductAStocks = 100;
        $initialProductBStocks = 50;
        $initialProductCStocks = 20;
        $initialProductDStocks = 20;

        $productA->update([
            'quantity' => $initialProductAStocks
        ]);
        $productB->update([
            'quantity' => $initialProductBStocks
        ]);
        $productC->update([
            'quantity' => $initialProductCStocks
        ]);
        $productD->update([
            'quantity' => $initialProductDStocks
        ]);

        $orNumber = 11111111;

        $saleTransaction = $this->standardStaffPost('/api/tracking/transactions/sale', [
            'or_no' => $orNumber,
            'items' => [
                [
                    'product_variation_id' => 1,
                    'quantity' => 50
                ],
                [
                    'product_variation_id' => 2,
                    'quantity' => 10
                ]
            ],
            'customer_id' => $this->getCustomerId()
        ]);

        $saleId = $saleTransaction->json()['data']['transaction']['id'];

        //SHORTOVER MUST FAIL ATTEMPT 1
        $shortOverTransaction1 = $this->coordinatorStaffPost('/api/tracking/transactions/products/shortover', [
            'or_no' => $orNumber,
            'items' => [
                [
                    'product_variation_id' => 1,
                    'shortover_product_variation_id' => 3,
                    'quantity' => 60
                ]
            ],
            'remarks' => 'Short/Over Testing'

        ]);

        $shortOverTransaction1->assertJsonFragment(['type' => 'error', 'data' => []]);


        //SHORTOVER SUCCESS ATTEMPT 1
        $shortOverTransaction2 = $this->coordinatorStaffPost('/api/tracking/transactions/products/shortover', [
            'or_no' => $orNumber,
            'items' => [
                [
                    'product_variation_id' => 1,
                    'shortover_product_variation_id' => 3,
                    'quantity' => 20
                ]
            ],
            'remarks' => 'Short/Over Testing'

        ]);

        $shortOverTransaction2Id = $shortOverTransaction2->json()['data']['transaction']['id'];

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();
        $productC = BranchStock::where('product_variation_id',3)->where('branch_id',1)->first();

        $this->assertEquals(30, $productA->quantity, "Expects stocks to be 30 as it is subtracted by by 20");
        $this->assertEquals(40, $productC->quantity, "Expects stocks to be 40 as it is added by 20");


        //SHORTOVER SUCCESS MUST FAIL ATTEMPT 2
        $shortOverTransaction3 = $this->coordinatorStaffPost('/api/tracking/transactions/products/shortover', [
            'or_no' => $orNumber,
            'items' => [
                [
                    'product_variation_id' => 1,
                    'shortover_product_variation_id' => 3,
                    'quantity' => 40
                ]
            ],
            'remarks' => 'Short/Over Testing'

        ]);

        $shortOverTransaction3->assertJsonFragment(['type' => 'error', 'data' => []]);

        //SHORTOVER SUCCESS ATTEMPT 2
        $shortOverTransaction4 = $this->coordinatorStaffPost('/api/tracking/transactions/products/shortover', [
            'or_no' => $orNumber,
            'items' => [
                [
                    'product_variation_id' => 1,
                    'shortover_product_variation_id' => 4,
                    'quantity' => 30
                ]
            ],
            'remarks' => 'Short/Over Testing'

        ]);

        $shortOverTransaction4Id = $shortOverTransaction4->json()['data']['transaction']['id'];

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();
        $productD = BranchStock::where('product_variation_id',4)->where('branch_id',1)->first();

        $this->assertEquals(0, $productA->quantity, "Expects stocks to be 0 as it is subtracted by by 30");
        $this->assertEquals(50, $productD->quantity, "Expects stocks to be 50 as it is added by 30");


        // VOID SHORTOVER SUCCESS ATTEMPT 1
        $voidShortOverTransaction1 = $this->coordinatorStaffPost('/api/tracking/transactions/products/shortover/'.$shortOverTransaction4Id.'/void', []);

        $voidShortOverTransaction1->assertJsonFragment(['type' => 'success']);

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();
        $productD = BranchStock::where('product_variation_id',4)->where('branch_id',1)->first();

        $this->assertEquals(30, $productA->quantity, "Expects stocks to be 30 as it is added by by 30");
        $this->assertEquals(20, $productD->quantity, "Expects stocks to be 20 as it is subtracted by 30");


        //FINALLY VOID TRANSACTION
        $voidSaleTransaction = $this->coordinatorStaffPost('/api/tracking/transactions/'.$saleId.'/void', []);

        $productA = BranchStock::where('product_variation_id',1)->where('branch_id',1)->first();
        $productB = BranchStock::where('product_variation_id',2)->where('branch_id',1)->first();
        $productC = BranchStock::where('product_variation_id',3)->where('branch_id',1)->first();
        $productD = BranchStock::where('product_variation_id',4)->where('branch_id',1)->first();

        $this->assertEquals(100, $productA->quantity, "Expects stocks to be 100 (original)");
        $this->assertEquals(50, $productB->quantity, "Expects stocks to be 50 (original)");
        $this->assertEquals(20, $productC->quantity, "Expects stocks to be 20 (original)");
        $this->assertEquals(20, $productD->quantity, "Expects stocks to be 20 (original)");


    }

    /** @aaatest */
    public function mustHandleMultipleItemSaleTransaction()
    {

        // TODO: create 5 new products
        // TODO: create 8 new product variations each product
        // TODO: add company stocks for each of 10
        // TODO: add branch stocks for each of 10

        // TODO: create sale containing 40 products and must handle it

    }

}
