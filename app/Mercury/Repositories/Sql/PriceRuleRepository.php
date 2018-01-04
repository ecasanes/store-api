<?php namespace App\Mercury\Repositories;

use App\Mercury\Helpers\StatusHelper;
use App\Mercury\Helpers\SqlHelper;
use App\PriceRule as Pricing;

use App\PriceRule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PriceRuleRepository implements PriceRuleInterface
{

    public function getAll()
    {
        $activeFlag = StatusHelper::ACTIVE;

        $disabledFlag = StatusHelper::DISABLED;

        $pricingList = Pricing::where('status', $activeFlag)->orWhere('status', $disabledFlag)->get();

        return $pricingList;
    }

    public function find($id)
    {
        $pricing = Pricing::find($id);

        return $pricing;
    }

    public function findByCode($code)
    {
        $code = strtolower($code);
        $pricing = Pricing::where('code', $code)->first();

        return $pricing;
    }

    public function filter(array $filter)
    {
        $additionalSqlFilters = $this->getAdditionalSqlFilters($filter);
        $paginationSql = SqlHelper::getPaginationByFilter($filter);

        $activeFlag = StatusHelper::ACTIVE;

        $disabledFlag = StatusHelper::DISABLED;

        $query = "SELECT
                    price_rules.name,
                    products.name as product_name,
                    product_variations.size,
                    product_variations.metrics,
                    price_rules.type,
                    price_rules.discount_type,
                    price_rules.discount,
                    price_rules.amount,
                    price_rules.quantity,
                    price_rules.apply_to,
                    price_rules.status,
                    price_rules.id,
                    price_rules.code,
                    price_rules.description,
                    price_rules.product_variation_id,
                    product_variations.product_id
                FROM price_rules
                  LEFT JOIN product_variations
                    ON product_variations.`id` = price_rules.`product_variation_id`
                  LEFT JOIN products
                    ON products.`id` = product_variations.`product_id`
                {$additionalSqlFilters}
                {$paginationSql}";
//        dump($filter);
//        dump($query);
        $priceRules = DB::select($query);
//        dd($priceRules);

        return $priceRules;
    }

    public function getFilterMeta($filter)
    {
        $limit = SqlHelper::getLimitByFilter($filter);
        $page = SqlHelper::getPageByFilter($filter);

        return [
            'length' => $this->getCountByFilter($filter),
            'limit' => $limit,
            'page' => $page
        ];
    }

    public function getCountByFilter(array $filter = [])
    {
        $activeFlag = StatusHelper::ACTIVE;
        $additionalSqlFilters = $this->getAdditionalSqlFilters($filter);

        $query = "SELECT 
                  count(*) as price_rules_count
                FROM
                  price_rules 
                WHERE price_rules.status = '{$activeFlag}'
                {$additionalSqlFilters} ";

        $priceRules = DB::select($query);

        return $priceRules[0]->price_rules_count;
    }


    public function create(array $data)
    {
//        $discount = $this->checkForPercentSign($data['discount']);

        $pricing = Pricing::create($data);

        return $pricing;
    }

    public function update($id, $data)
    {
        $pricing = Pricing::find($id);

        if(!$pricing) {
            return false;
        }

        $updated = $pricing->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $pricing = Pricing::find($id);

        if(!$pricing) {
            return false;
        }

        if($pricing->status == StatusHelper::DELETED) {
            return true;
        }

        $deleted = $pricing->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
            'name' => StatusHelper::flagDelete($pricing->name),
            'code' => StatusHelper::flagDelete($pricing->code)
        ]);

        return $deleted;
    }

    public function destroy($id)
    {
        $pricing = $this->find($id);

        if(!$pricing) {
            return false;
        }

        $destroyed = $pricing->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $pricing = $this->find($id);

        if(!$pricing){
            return true;
        }

        if($pricing->status != StatusHelper::DELETED){
            return false;
        }

        return true;
    }

    private function getAdditionalSqlFilters($filter)
    {
        $querySql = "";
        $variationSql = "";
        $ruleTypeSql = "";
        $discountTypeSql = "";
        $customerTypeSql = "";
        $sortSql = "";

        $order = 'DESC';

        if(isset($filter['variation_id'])) {
            $variationId = $filter['variation_id'];
            $variationSql = " AND price_rules.product_variation_id = {$variationId}";
        }

        if(isset($filter['rule_type'])) {
            $ruleType = $filter['rule_type'];
            $ruleTypeSql = " AND price_rules.type = '{$ruleType}'";
        }

        if(isset($filter['discount_type'])) {
            $discountType = $filter['discount_type'];
            $discountTypeSql = " AND price_rules.discount_type = '{$discountType}'";
        }

        if(isset($filter['customer_type'])) {
            $customerType = $filter['customer_type'];
            $customerTypeSql = " AND price_rules.apply_to = '{$customerType}'";
        }

        if(isset($filter['order'])) {
            $order = $filter['order'];
        }

        if(isset($filter['sort'])) {
            $sort = $filter['sort'];

            $sortSql = "ORDER BY price_rules.{$sort} {$order}";

            if($sort == 'product_name') {
                $sortSql = "ORDER BY products.name {$order}";
            }
        }

        if(isset($filter['q'])){
            $query = $filter['q'];
            $querySql = " AND LOWER(
                CONCAT(
                    price_rules.name,
                    ' ',
                    price_rules.code,
                    ' ',
                    price_rules.type,
                    ' ',
                    price_rules.discount,
                    ' ',
                    price_rules.apply_to,
                    ' '
                )
            ) 
            LIKE LOWER('%{$query}%') ";
        }

        $additionalSql = $querySql . $variationSql . $ruleTypeSql . $discountTypeSql . $customerTypeSql . $sortSql;

        return $additionalSql;
    }

    private function checkForPercentSign($string)
    {
        if(strpos($string, '%') === false) {
            return $string.'%';
        }

        return $string;
    }


    public function getPriceRulesByApplication($applicationType = StatusHelper::ALL_FLAG)
    {
        $all = StatusHelper::ALL_FLAG;

        $priceRules = Pricing::whereIn('apply_to',[$all, $applicationType])->get()->toArray();

        return $priceRules;
    }


    public function getPriceRulesByType($ruleType)
    {
        $priceRules = PriceRule::where('type', $ruleType)->get();

        return $priceRules;
    }
}