<?php namespace App\Mercury\Services\Traits\Activity;

trait MessageTrait {

    // TODO refactor
    public function setMessageData($user, $activityLogTypeCode, $transaction = null)
    {

        if(!$transaction) {

            $message = $this->setNonTransactionActivitiesMessage($user, $activityLogTypeCode);

        }

        if($transaction && !empty($transaction)) {

            $message = $this->setTransactionActivitiesMessage($user, $activityLogTypeCode, $transaction);

        }

        return $message;

    }

    public function setNonTransactionActivitiesMessage($user, $code)
    {
        $name = $user->firstname.' '.$user->lastname;

        $email = $user->email;

        $branchId = $user->branch_id_registered;

        switch ($code) {

            case 'login':
                $message = $name.' logged in';
                break;

            case 'logout':
                $message = $name.' logged out';
                break;

            case 'add_pending_delivery':
                $message = $name.' has added a pending delivery';
                break;

            case 'confirm_pending_delivery':
                $message = $name.' has confirmed a pending delivery';
                break;

            case 'void_pending_delivery':
                $message = $name.' has made void a pending delivery';
                break;

            case 'return_pending_delivery':
                $message = $name.' has returned a pending delivery';
                break;

            case 'add_branch':
                $message = $name.' added a branch to the database';
                break;

            case 'update_branch':
                $message = $name.' updated information on a branch';
                break;

            case 'delete_branch':
                $message = $name.' has deleted a branch record';
                break;

            case 'add_product':
                $message = $name.' added a product to the database';
                break;

            case 'update_product':
                $message = $name.' updated information on a product';
                break;

            case 'delete_product':
                $message = $name.' has deleted a product record';
                break;

            case 'add_user':
                $message = $name.' added a user to the database';
                break;

            case 'update_user':
                $message = $name.' updated information on a user';
                break;

            case 'delete_user':
                $message = $name.' has deleted a user record';
                break;

            default:
                $message = 'Unable to create activity message';
        }

        return $message;
    }

    public function setTransactionActivitiesMessage($user, $code, $transaction)
    {
        $name = $user->firstname.' '.$user->lastname;

        $products = $transaction->items;

        $limit = 2;

        $counter = 0;
        foreach($products as $product) {

            if($counter==$limit){
                continue;
            }

            $productSize = "";
            $productMetrics = "";
            $quantity = "";

            if($product->product_name && !empty($product->product_name)) {
                $productName = $product->product_name;
            }

            if($product->product_size && !empty($product->product_size)) {
                $productSize = $product->product_size;
            }

            if($product->product_metrics && !empty($product->product_metrics)) {
                $productMetrics = $product->product_metrics;
            }

            if($product->quantity && !empty($product->quantity)) {
                $quantity = $product->quantity;
            }

            $variation = $productSize.' '.$productMetrics;

            $productVariation = $productName.' '.'('.$variation.')';

            $productsArray[] = $productVariation;

            $counter++;
        }

        $transactionItems = implode(" ", $productsArray);


        // TODO refactor this to be dynamic
        switch($code) {
            case 'request_stock':
                $message = $name.' has requested stocks for '.$transactionItems;
                break;

            case 'deliver_stock':
                $message = $name.' has confirmed stock delivery for '.$transactionItems;
                break;

            case 'return_stock':
                $message = $name.' has returned stocks for '.$transactionItems;
                break;

            case 'restock_product':
                $message = $name.' has restocked '.$transactionItems;
                break;

            case 'subtract_stock':
                $message = $name.' has subtracted stocks from '.$transactionItems;
                break;

            case 'add_sale':
                $message = $name.' has sold '.$transactionItems;
                break;

            case 'void_sale':
                $message = $name.' has voided a sale of '.$transactionItems;
                break;

            case 'void_return_sale':
                $message = $name.' has voided a return sale of '.$transactionItems;
                break;

            case 'void_short_sale':
                $message = $name.' has voided a short sale of '.$transactionItems;
                break;

            case 'void_shortover_sale':
                $message = $name.' has voided a shortover sale of '.$transactionItems;
                break;

            case 'return_sale':
                $message = $name.' has returned a sale of '.$transactionItems;
                break;

            case 'shortover_sale':
                $message = $name.' has declared a shortover sale of '.$transactionItems;
                break;

            case 'short_sale':
                $message = $name.' has declared a short sale of '.$transactionItems;
                break;

            case 'short_adjustment':
                $message = $name.' has made a short adjustment of '.$transactionItems;
                break;

            case 'shortover_adjustment':
                $message = $name.' has made a shortover adjustment of '.$transactionItems;
                break;

            case 'void_short_adjustment':
                $message = $name.' has made void a short adjustment on '.$transactionItems;
                break;

            case 'void_shortover_adjustment':
                $message = $name.' has made void a shortover adjustment on '.$transactionItems;
                break;

            default:
                $message = 'Error. Could not log transaction activity';
        }

        return $message;
    }
}
