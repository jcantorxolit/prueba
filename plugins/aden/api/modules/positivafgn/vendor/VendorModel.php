<?php

namespace AdeN\Api\Modules\PositivaFgn\Vendor;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class VendorModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_vendor";
	
    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'foreignKey' => 'createdBy', 'parentKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'foreignKey' => 'updatedBy', 'parentKey' => 'id']
    ];

    public $hasMany = [
        "contacts" => ["AdeN\Api\Modules\PositivaFgn\Vendor\ContactInformationModel", 'key' => 'vendor_id', 'otherKey' => 'id'],
        "strategys" => ["AdeN\Api\Modules\PositivaFgn\Vendor\StrategyModel", 'key' => 'vendor_id', 'otherKey' => 'id']
    ];


    public function getDocumentType()
    {
        return $this->getParameterByValue($this->documentType, "employee_document_type");
    }

    public function getDepartment()
    {
        return DB::table("rainlab_user_states")
                ->select("id","name")
                ->where("id", $this->departmentId)
                ->first();
    }

    public function getTown()
    {
        return DB::table("wg_towns")
                ->select("id","name")
                ->where("id", $this->townId)
                ->first();
    }


	protected function getParameterByValue($value, $group, $ns = "wgroup") {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}