<?php namespace App\Mercury\Services\Traits\Transaction;

trait ReportTrait
{

    public function getTotalSales($filter)
    {
        //$totalSales = $this->transaction->getTotalSales($filter);
        $totalSales = $this->transaction->getTotalNetSales($filter);

        return $totalSales;
    }

    public function getTopSaleItems($filter)
    {
        $topItems = $this->transaction->getTopItems($filter);

        return $topItems;
    }

    public function getSalesSummary($filter)
    {
        // deprecated: due to sales summary being calculated in database level for much faster performance
        /*$revenue = $this->transaction->getTotalSales($filter);
        $costOfSales = $this->transaction->getTotalCostOfSales($filter);
        $returnSales = $this->transaction->getTotalReturnSales($filter);
        $discounts = $this->transaction->getTotalDiscounts($filter);

        $netSales = $this->calculateNetSales($revenue, $returnSales, $discounts);
        $grossProfit = $this->calculateGrossProfit($revenue, $costOfSales);
        $grossProfitMargin = $this->calculateGrossProfitMargin($grossProfit, $revenue);*/

        $salesSummary = $this->transaction->getSalesSummary($filter);

        // deprecated: due to sales summary being calculated in database level for much faster performance
        /*return [
            'sales' => $revenue,
            'returns' => $returnSales,
            'cost_of_sales' => $costOfSales,
            'discounts' => $discounts,
            'net_sales' => $netSales,
            'gross_profit' => $grossProfit,
            'gross_margin' => $grossProfitMargin
        ];*/

        return $salesSummary;
    }

    public function getSalesChanges($filter)
    {
        $salesChanges = [];

        if(!isset($filter['range'])){
            $filter['range'] = 'daily';
        }

        $currentSalesSummary = $this->transaction->getSalesSummary($filter);

        $filter['previous_date'] = 1;

        $previousSalesSummary = $this->transaction->getSalesSummary($filter);

        foreach ($currentSalesSummary as $key => $value) {

            foreach ($previousSalesSummary as $previousKey => $previousValue) {

                $currentSalesChange = $value - $previousValue;

                if ($previousKey == $key) {
                    $salesChanges[$key.'_previous'] = $previousValue;
                    $salesChanges[$key.'_current'] = $value;
                    $salesChanges[$key.'_change'] = $currentSalesChange;
                }

                if ($previousKey == $key && $currentSalesChange <= 0) {
                    $decreaseValue = ($previousValue<=0)?0:(abs($currentSalesChange) / $previousValue);
                    $salesChanges[$key . '_increase'] = 0;
                    $salesChanges[$key . '_decrease'] = $decreaseValue;
                    $salesChanges[$key.'_state'] = 'decrease';
                }

                if ($previousKey == $key && $currentSalesChange > 0) {
                    $increaseValue = ($value<=0)?0:(abs($currentSalesChange) / $value);
                    $salesChanges[$key . '_increase'] = $increaseValue;
                    $salesChanges[$key . '_decrease'] = 0;
                    $salesChanges[$key.'_state'] = 'increase';
                }
            }



        }

        return $salesChanges;

    }

    public function getProductsSalesSummary($filter)
    {
        $productsSummary = $this->transaction->getProductsSalesSummary($filter);

        return $productsSummary;
    }

    public function getAllProductsSalesSummary($filter)
    {
        $productsSummary = $this->transaction->getAllProductsSalesSummary($filter);

        return $productsSummary;
    }

    public function getDailySales($filter)
    {
        $dailySales = $this->transaction->getDailySales($filter);

        return $dailySales;
    }


    private function calculateNetSales($revenue, $returnSales, $discounts)
    {

        return $revenue - $returnSales - $discounts;

    }

    private function calculateGrossProfit($revenue, $costOfSales)
    {

        return $revenue - $costOfSales;

    }

    private function calculateGrossProfitMargin($grossProfit, $revenue)
    {
        if ($revenue <= 0) {
            return 0;
        }

        return $grossProfit / $revenue;

    }

}