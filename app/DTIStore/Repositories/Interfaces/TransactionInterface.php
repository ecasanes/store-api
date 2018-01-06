<?php namespace App\DTIStore\Repositories;

interface TransactionInterface
{
    public function create(array $data);

    public function find($id);

    public function getAll();

    public function getCountByFilter(array $filter);

    public function filter(array $filter);

    public function getFilterMeta(array $filter);

    public function update($id, $data);

    public function delete($id);

    public function destroy($id);

    public function isDeleted($id);

    public function findByOr($orNo, $transactionTypeCode, $includeVoid = false, $branchId = null);

    public function findReturnSaleByOr($orNo, $branchId = null);

    public function getByOr($orNumber, $transactionTypeCode, $branchId = null);

    public function getByInvoiceNo($invoiceNo, $transactionTypeCode);

    public function getTotalSales($filter);

    public function getTopItems($filter);

    public function getTotalCostOfSales($filter);

    public function getTotalReturnSales($filter);

    public function getTotalDiscounts($filter);

    public function getTotalNetSales($filter);

    public function getGrossProfit($filter);

    public function getGrossProfitMargin($filter);

    public function getProductsSalesSummary($filter);

    public function getAllProductsSalesSummary($filter);

    public function getDailySales($filter);

    public function getReturnItemsByOrNumber($orNumber);

    public function getReturnItemsByInvoiceNo($invoiceNo);

    public function getProductRemainingDeliverySaleQuantityByInvoiceNo($productVariationId, $invoiceNo);

    public function getBranchesSalesSummary($filter);

    public function getSalesSummary($filter);

    public function getWarehouseLedger($filter);

    public function getWarehouseLedgerMeta($filter);

    public function getBranchLedger($filter);

    public function getBranchLedgerMeta($filter);

    public function getAllOrNumbersByBranchId($id);

}