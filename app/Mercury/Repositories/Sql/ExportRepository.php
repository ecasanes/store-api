<?php namespace App\Mercury\Repositories;

use Carbon\Carbon;
use App\Users;
use Excel;

class ExportRepository implements ExportInterface
{
    public function export($tableData, $code, $headings)
    {
        # unset token
        unset($tableData['token']);

        if($code == 'deliveries') {
            $fileName = $this->getDeliveryExportFileName($tableData);
            $tableData = $tableData['export'];
        }

        $data[] = $headings;

        foreach($tableData as $key => $push) {
            $data[] = $push;
        }


        if($code != 'deliveries') {
            $fileName = $this->getFileName($code);
        }

        $excel = Excel::create($fileName, function($excel) use ($data, $code) {

            $excel->setTitle(ucfirst($code));

            $excel->sheet('Sheet 1', function($sheet) use ($data) {

                $sheet->fromArray($data, null, 'A1', false, false);

            });

        })->store('xls', false, true);

        return $excel['file'];
    }

    public function exportStocksOnHand($data, $branches, $headings, $code, $filters)
    {
        $from = $filters['from'];
        $to = $filters['to'];

        $fileName = $this->getFileName($code, $from, $to);

        $branchId = $filters['branch_id'];

        # if export all stock data
        if($branchId == 0) {

            $companyStocks = $data['company_stocks'][0];
            $branchStocks = $data['branch_stocks'];

            array_unshift($companyStocks, $headings);

            $excel = Excel::create($fileName, function($excel) use ($companyStocks, $branchStocks, $headings, $code, $branches) {

                $excel->setTitle(ucfirst($code));

                $excel->sheet('Warehouse', function($sheet) use ($companyStocks) {
                    $sheet->fromArray($companyStocks, null, 'A1', true, false);
                });

                foreach($branchStocks as $branchStock) {

                    array_unshift($branchStock['branch_stocks'], $headings);

                    $excel->sheet($branchStock['branch_name'], function($sheet) use ($branchStock) {
                        $sheet->fromArray($branchStock['branch_stocks'], null, 'A1', true, false);
                    });
                }

            })->store('xls', false, true);
        }

        # if export only for a certain branch
        if($branchId > 0) {

            $branchStocksData = $data['branch_stocks'];

            $excel = Excel::create($fileName, function($excel) use ($branchStocksData, $headings, $code) {
                $excel->setTitle(ucfirst($code));

                $excel->sheet($branchStocksData['branch_name'], function($sheet) use ($branchStocksData, $headings) {
                    array_unshift($branchStocksData['branch_stocks'], $headings);
                    $sheet->fromArray($branchStocksData['branch_stocks'], null, 'A1', true, false);
                });
            })->store('xls', false, true);
        }

        return $excel['file'];

    }

    private function getFileName($code, $from = null, $to = null)
    {
        $date = Carbon::now()->format('DMdY_hiA');

        if($from && $to) {

            if($from == $to) {
                $date = Carbon::parse($from)->format('DMdY');
                $currentTime = Carbon::now()->format('hiA');
                $date = $date.'_'.$currentTime;
            } else {
                $fromDate = Carbon::parse($from)->format('DMdY');
                $toDate = Carbon::parse($to)->format('DMdY');
                $date = $fromDate."_to_".$toDate;
            }

        }

        if($code != 'stocks') {

            $timestamp = Carbon::now()->timestamp;

            $fileName = $code.'-'.$timestamp;
        }

        if($code == 'stocks') {
            $fileName = 'StocksOnHand_'.$date;
        }

        return $fileName;
    }

    private function getDeliveryExportFileName($data)
    {
        $status = $data['status'];

        $branch = $data['branch'];

        if(count($branch) > 1) {
            $branch = explode(" ", $branch);
            $branch = implode($branch);
        }

        $date = $this->parseReportDate($data['date']);

        switch(strtolower($status)) {
            case 'return':
                $status = 'ReturnedDelivery_';
                break;

            case 'pending':
                $status = 'PendingDelivery_';
                break;

            case 'confirmed':
                $status = 'Delivered_';
                break;
        }

        $fileName = $status.$branch."_".$date;

        return $fileName;
    }

    private function getFileNameForReport($code, $from, $to)
    {
        $now = $this->parseReportDate(Carbon::now()->toFormattedDateString());

        if($from) {
            $from = $this->parseReportDate(Carbon::parse($from)->toFormattedDateString());
        }

        if($to) {
            $to = $this->parseReportDate(Carbon::parse($to)->toFormattedDateString());
        }

        switch($code) {
            case 'general_summary':
                $fileName = 'General-Reports-'.$from.'-'.$to;
                if(!$from && !$to) {
                    $fileName = 'General-Reports-Created-On-'.$now;
                }
                break;

            case 'sales_summary':
                $fileName = 'Sales-Summary-'.$from.'-'.$to;
                if(!$from && !$to) {
                    $fileName = 'Sales-Summary-Created-On-'.$now;
                }
                break;

            case 'top_items':
                $fileName = 'Top-Products-'.$from.'-'.$to;
                if(!$from && !$to) {
                    $fileName = 'Top-Products-Created-On-'.$now;
                }
                break;
        }

        return $fileName;
    }

    private function parseReportDate(string $date)
    {
        $dateArr = [];

        $elements = explode(" ", $date);

        foreach($elements as $element) {

            $commaPos = strpos($element, ',');

            if($commaPos) {
                $element = substr($element, 0, $commaPos);
            }

            $dateArr[] = $element;
        }

        $date = implode($dateArr);

        return $date;
    }

    public function exportGeneralSummary($data, $branches, $from, $to)
    {
        $code = 'general_summary';

        $fileName = $this->getFileNameForReport($code, $from, $to);

        $columnHeadings = [
            ['Products', 'Item Sold', 'Revenue', 'Cost of Sales', 'Returns', 'Net Sales', 'Gross Profit', 'Gross Margin']
        ];

        $excel = Excel::create($fileName, function($excel) use ($data, $code, $branches, $columnHeadings) {
            $excel->setTitle(ucfirst($code));

            foreach($data as $key => $export) {
                $exportArr = [];
                $exportArr = $columnHeadings;
                $branchName = $data[$key]['branch_name'];
                unset($export['branch_name']); // unset branch_name
                foreach($export as $array) {

                    # discounts
                    if(isset($array->discounts)) {
                        unset($array->discounts);
                    } else {
                        unset($array['discounts']);
                    }

                    # quantity
                    if(isset($array->quantity)) {
                        $array->quantity = (int) $array->quantity;
                    } else {
                        unset($array['quantity']);
                    }
//
//                    # sales
//                    $array->sales = (float) $array->sales ;
//                    $array->sales = '₱ '.$array->sales;
//
//                    # cost of sales
//                    $array->cost_of_sales = (float) $array->cost_of_sales;
//                    $array->cost_of_sales = '₱ '.$array->cost_of_sales;
//
//                    # returns
//                    $array->returns = (float) $array->returns;
//                    $array->returns = '₱ '.$array->returns;
//
//                    # net sales
//                    $array->net_sales = (float) $array->net_sales;
//                    $array->net_sales = '₱ '.$array->net_sales;
//
//                    # gross profit
//                    $array->gross_profit = (float) $array->gross_profit;
//                    $array->gross_profit = '₱ '.$array->gross_profit;
                    # percentage
                    if(isset($array->gross_margin)) {
                        $array->gross_margin = $array->gross_margin * 100;
                        $array->gross_margin = $array->gross_margin . '%';
                    } else {
                        $array['gross_margin'] = $array['gross_margin'] * 100;
                        $array['gross_margin'] = $array['gross_margin'] . '%';
                    }

                    $exportArr[] = (array) $array;
                }
//                dd($exportArr);
                $excel->sheet($branchName, function($sheet) use ($exportArr) {
                    $sheet->fromArray($exportArr, null, 'A1', false, false);
                    $sheet->cells('A1:H1', function($cells) {
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                    });
                });
            }
        })->store('xls', false, true);

        return $excel['file'];
    }

    public function exportSalesSummary($data, $branches, $from, $to)
    {
        $code = 'sales_summary';

        $fileName = $this->getFileNameForReport($code, $from, $to);

        $columnHeadings = [
            'Store', 'Revenue', 'Cost of Sales', 'Net Sales', 'Gross Profit'
        ];

        $excel = Excel::create($fileName, function($excel) use ($data, $code, $branches, $columnHeadings) {
            $excel->setTitle(ucfirst($code));

            $exportArr = [];
            $exportArr[] = $columnHeadings;

            foreach($data as $key => $export) {
                $branchNameArray = ['branch_name' => $export['branch_name']];
                unset($data[$key]['returns']);
                unset($data[$key]['discounts']);
                unset($data[$key]['gross_margin']);
                array_pop($data[$key]);

                $data[$key] = $branchNameArray + $data[$key];
            }

            foreach($data as $exportData) {
                $exportArr[] = $exportData;
            }

            $excel->sheet('Sheet1', function($sheet) use ($exportArr) {
                $sheet->fromArray($exportArr, null, 'A1', false, false);
                $sheet->cells('A1:E1', function($cells) {
                    $cells->setBackground('#CCCCCC');
                    $cells->setFontWeight('bold');
                });
            });

        })->store('xls', false, true);

        return $excel['file'];
    }

    public function exportTopItems($data, $branches, $from, $to)
    {
        $code = 'top_items';

        $columnHeadings = [
            'Product', 'No. of Items Sold'
        ];

        $fileName = $this->getFileNameForReport($code, $from, $to);

        $excel = Excel::create($fileName, function($excel) use ($data, $code, $branches, $columnHeadings) {

            $excel->setTitle(ucfirst($code));

            foreach($data as $key => $export) {
                $exportArr = [];
                $exportArr[] = $columnHeadings;
                $branchName = $data[$key]['branch_name'];
                unset($export['branch_name']);

                foreach($export as $array) {
                    $topProduct = $array->product_name.' ('.$array->size.' '.$array->metrics.')';
                    $quantity = (int) $array->sum;
                    $productArr = ['product' => $topProduct, 'quantity' => $quantity];
                    $exportArr[] = $productArr;
                }

                $excel->sheet($branchName, function($sheet) use ($exportArr) {
                    $sheet->fromArray($exportArr, null, 'A1', false, false);
                    $sheet->cells('A1:B1', function($cells) {
                        $cells->setBackground('#CCCCCC');
                        $cells->setFontWeight('bold');
                    });
                });
            }
        })->store('xls', false, true);

        return $excel['file'];

    }

}