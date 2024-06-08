<?php

namespace Wgroup\Models;

use October\Rain\Database\Model;
use System\Models\Parameters;

class CustomerProjectCost extends Model{

    protected $table = 'wg_customer_project_costs';

    const STATUS_PROGRAMMED = 'SS001';
    const STATUS_EXECUTED   = 'SS002';

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public function getConcept() {
        return $this->getParameterByValue($this->concept, "project_concepts");
    }

    public function  getClassification()
    {
        return $this->getParameterByValue($this->classification, "project_classifications");
    }

}