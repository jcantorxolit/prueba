<?php

namespace AdeN\Api\Modules\Project\Documents;

use DB;
use System\Models\Parameters;
use October\Rain\Database\Model;

use AdeN\Api\Classes\CamelCasing;
use AdeN\Api\Modules\Customer\CustomerModel;

class ProjectDocumentModel extends Model
{
    use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_project_document";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public $attachOne = [
        'document' => ['System\Models\File']
    ];

    public function  getDocumentType()
    {
        return DB::table('wg_customers')
        ->join(DB::raw(CustomerModel::getDocumentTypeRelation('document_type')), function ($join) {

            $join->on('wg_customers.id', '=', 'document_type.customer_id')
                ->whereNull('document_type.customer_id', 'or');

        })
        ->select('document_type.*')
        ->where('document_type.value', $this->type)
        ->where('document_type.origin', $this->origin)
        ->orderBy('document_type.item')
        ->first();
    }

    public function  getClassification()
    {
        return $this->getParameterByValue($this->classification, "customer_document_classification");
    }

    public function  getStatus()
    {
        return $this->getParameterByValue($this->status, "customer_document_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
