<?php namespace App\DTIStore\Services;

use App\DTIStore\Helpers\StatusHelper;
use App\DTIStore\Repositories\OrderInterface;
use App\DTIStore\Repositories\StoreInterface;
use App\DTIStore\Repositories\StoreStockInterface;
use App\DTIStore\Repositories\ProductInterface;
use App\DTIStore\Repositories\ProductVariationInterface;
use App\DTIStore\Repositories\TransactionInterface;
use App\DTIStore\Repositories\TransactionItemInterface;
use App\DTIStore\Repositories\TransactionTypeInterface;
use App\DTIStore\Repositories\UserInterface;
use App\DTIStore\Services\Traits\Transaction\BranchStockTransactionsTrait;
use App\DTIStore\Services\Traits\Transaction\CompanyStockTransactionsTrait;
use App\DTIStore\Services\Traits\Transaction\DeliverySaleTransactionTrait;
use App\DTIStore\Services\Traits\Transaction\DeliveryTransactionsTrait;
use App\DTIStore\Services\Traits\Transaction\FranchiseeSaleTransactionTrait;
use App\DTIStore\Services\Traits\Transaction\ReportTrait;
use App\DTIStore\Services\Traits\Transaction\SaleTransactionTrait;

class OrderService
{
    protected $product;
    protected $user;
    protected $variation;
    protected $transaction;
    protected $transactionItem;
    protected $transactionType;
    protected $branch;
    protected $storeStock;
    protected $order;

    protected $voidFlag = StatusHelper::VOID;

    public function __construct(
        ProductInterface $product,
        ProductVariationInterface $variation,
        TransactionInterface $transaction,
        TransactionItemInterface $transactionItem,
        TransactionTypeInterface $transactionType,
        StoreInterface $branch,
        StoreStockInterface $storeStock,
        UserInterface $user,
        OrderInterface $order
    )
    {
        $this->product = $product;
        $this->variation = $variation;
        $this->transaction = $transaction;
        $this->transactionItem = $transactionItem;
        $this->transactionType = $transactionType;
        $this->branch = $branch;
        $this->storeStock = $storeStock;
        $this->user = $user;
        $this->order  = $order;
    }

    public function filter($data)
    {

    }

    public function create($data)
    {
        $order = $this->order->create($data);

        return $order;
    }

    public function update()
    {

    }

    public function delete()
    {

    }

    public function createTransactionsByProducts($orderId, $products, $paymentModeId = null, $discount = 0)
    {
        $paymentMode = $this->product->findPaymentModeById($paymentModeId);
        $paymentModeCode = null;

        if($paymentMode){
            $paymentModeCode = $paymentMode->code;
        }

        $buyerStatus = 'to_pay';

        switch($paymentModeCode){
            case 'debit':
                $buyerStatus = 'paid';
                break;
            case 'credit':
                $buyerStatus = 'paid';
                break;
        }

        $productsByStores = [];
        $createdRows = [
            'transactions' => [],
            'transaction_items' => []
        ];

        foreach($products as $product) {

            $storeId = $product['store_id'];
            $productsByStores[$storeId][] = $product;

        }

        $storeCount = count($productsByStores);

        foreach($productsByStores as $storeId => $storeProducts){

            $total = 0;

            $transaction = $this->transaction->create([
                'store_id' => $storeId,
                'order_id' => $orderId,
                'buyer_status' => $buyerStatus
            ]);

            $createdRows['transactions'][] = $transaction;

            foreach($storeProducts as $product){

                $quantity = $product['quantity'];
                $sellingPrice = $product['selling_price'];
                $shippingPrice = $product['shipping_price'];

                $transactionItem = $this->transactionItem->create([
                    'product_variation_id' => $product['product_variation_id'],
                    'quantity' => $quantity,
                    'product_name' => $product['name'],
                    'selling_price' => $sellingPrice,
                    'shipping_price' => $shippingPrice,
                    'transaction_id' => $transaction->id
                ]);

                $total += ($sellingPrice*$quantity)+($shippingPrice*$quantity);

                if($discount>0){
                    $total = $total - ($discount/$storeCount);
                }


                $createdRows['transaction_items'][] = $transactionItem;

            }

            $this->transaction->update($transaction->id, [
                'total' => $total
            ]);

        }

        return $createdRows;


    }

    public function createTransaction()
    {

    }

    public function updateTransaction($transactionId, $data)
    {
        $updated = $this->transaction->update($transactionId, $data);

        return $updated;
    }

    public function deleteTransaction()
    {

    }

    public function createTransactionItem()
    {

    }

    public function updateTransactionItem()
    {

    }

    public function deleteTransactionItem()
    {

    }

    public function getOrderTransactions($filter)
    {

        $orders = $this->order->getOrderTransactions($filter);

        return $orders;
    }

    public function findTransaction($transactionId)
    {
        $transaction = $this->transaction->find($transactionId);

        return $transaction;
    }

    public function generateTrackingNo()
    {
        $trackingNo = null;
        $notUnique = true;

        while($notUnique){

            $trackingNo = rand(10000000, 99999999);

            $transaction = $this->transaction->findByTrackingNo($trackingNo);

            if($transaction){
                continue;
            }

            $notUnique = false;

        }

        return $trackingNo;
    }

}