<?php namespace App\Mercury\Helpers;

use Carbon\Carbon;

class SqlHelper
{
    public static function getPaginationSql($page = null, $limit = null)
    {
        if($limit == StatusHelper::NONE){
            return "";
        }

        $limit = SqlHelper::getDefaultLimit($limit);

        if ($page <= 0 || $page == "" || empty($page)) {
            $page = 1;
        }

        $offset = $limit * ($page-1);

        if($page <= 1){
            $offset = 0;
        }

        $paginationSql = " LIMIT {$limit} OFFSET {$offset} ";

        return $paginationSql;

    }

    public static function getPaginationByFilter(array $filter = [])
    {
        $page = 1;

        $limit = SqlHelper::getDefaultLimit();

        if(isset($filter['source'])) {
            if($filter['source'] == 'export') {
                $limit = 'none';
            }
        }

        if(isset($filter['page'])){
            $page = $filter['page'];
        }

        if(isset($filter['limit'])){
            $limit = $filter['limit'];
        }

        $paginationSql = SqlHelper::getPaginationSql($page, $limit);

        return $paginationSql;
    }

    public static function getDefaultLimit($limit = null)
    {
        if(empty($limit) || trim($limit) == ""){
            $limit = 10;
        }

        return $limit;

    }

    public static function getDefaultPage($page = null)
    {
        if(empty($page) || trim($page) == ""){
            $page = 1;
        }

        return $page;

    }

    public static function getLimitByFilter($filter)
    {

        $limit = null;

        if(isset($filter['limit'])){
            $limit = $filter['limit'];
        }

        return SqlHelper::getDefaultLimit($limit);

    }

    public static function getPageByFilter($filter)
    {
        $page = null;

        if(isset($filter['page'])){
            $page = $filter['page'];
        }

        return SqlHelper::getDefaultPage($page);

    }
}