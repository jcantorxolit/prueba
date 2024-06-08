<?php
/**
 * Created by PhpStorm.
 * User: David Blandon
 * Date: 4/25/2016
 * Time: 5:43 PM
 */

namespace AdeN\Api\Classes;


class Criteria
{
    public $currentPage = 1;
    public $pageSize = 10;
    public $logic = "and";
    public $mandatoryFilters = array();
    public $filter = null;
    public $search = null;
    public $sorts = array();
    public $draw = 1;
    public $lang = "de";
    public $type = "";
    public $take = 10;
    public $skip = 0;
}