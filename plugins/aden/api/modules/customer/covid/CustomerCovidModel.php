<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\Covid;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerCovidModel extends Model
{
    use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_covid_head";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public $attachOne = [];

    public function getDocumentTYpe()
    {
        return $this->getParameterByValue($this->documentType, "employee_document_type");
    }

    public function  getContractType()
    {
        return $this->getParameterByValue($this->contractType, "employee_contract_type");
    }

    public function  getExternalType()
    {
        return $this->getParameterByValue($this->externalType, "customer_covid_external_type");
    }

    public function getWorkplace()
    {
        return DB::table('wg_customer_config_workplace')
            ->select(
                'wg_customer_config_workplace.id',
                'wg_customer_config_workplace.name'
            )
            ->where('id', $this->customerWorkplaceId)
            ->first();
    }

    public function getContractor()
    {
        return  DB::table('wg_customers')
            ->join('wg_customer_covid_head', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_covid_head.contractor_id');
            })
            ->select(
                'wg_customers.id',
                'wg_customers.id AS value',
                'wg_customers.businessName AS item'
            )
            ->where('wg_customer_covid_head.contractor_id', $this->contractorId)
            ->first();
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public static function getExternalTypes()
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("customer_covid_external_type")->get();
    }
}
