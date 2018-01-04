<?php namespace App\Mercury\Repositories;

interface ExportInterface
{
    public function export($data, $code, $headings);

    public function exportStocksOnHand($data, $branches, $headings, $code, $filters);

    public function exportGeneralSummary($data, $branches, $from, $to);

    public function exportSalesSummary($data, $branches, $from, $to);

    public function exportTopItems($data, $branches, $from, $to);

}