<?php

namespace AdeN\Api\Modules\PositivaFgn\Consultant;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class ConsultantModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_consultant";
	
    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'foreignKey' => 'createdBy', 'parentKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'foreignKey' => 'updatedBy', 'parentKey' => 'id']
    ];

    public $hasMany = [
        "licenses" => ["AdeN\Api\Modules\PositivaFgn\Consultant\LicenseModel", 'key' => 'consultant_id', 'otherKey' => 'id'],
        "contacts" => ["AdeN\Api\Modules\PositivaFgn\Consultant\ContactInformationModel", 'key' => 'consultant_id', 'otherKey' => 'id'],
        "strategys" => ["AdeN\Api\Modules\PositivaFgn\Consultant\StrategyModel", 'key' => 'consultant_id', 'otherKey' => 'id']
    ];


    public function getType()
    {
        return $this->getParameterByValue($this->type, "positiva_fgn_consultant_type");
    }

    public function getDocumentType()
    {
        return $this->getParameterByValue($this->documentType, "employee_document_type");
    }

    public function getGender()
    {
        return $this->getParameterByValue($this->gender, "gender");
    }

    public function getGrade()
    {
        return $this->getParameterByValue($this->grade, "positiva_fgn_consultant_grade");
    }

    public function getAccountingAccount()
    {
        return $this->getParameterByValue($this->accountingAccount, "accounting_account");
    }

    public function getWorkingDay()
    {
        return $this->getParameterByValue($this->workingDay, "positiva_fgn_consultant_workday");
    }

    public function getEps()
    {
        return $this->getParameterByValue($this->eps, "eps");
    }

    public function getAfp()
    {
        return $this->getParameterByValue($this->afp, "afp");
    }

    public function getCcf()
    {
        return $this->getParameterByValue($this->ccf, "ccf");
    }

    public function getAccountType()
    {
        return $this->getParameterByValue($this->accountType, "account_type");
    }

	protected function getParameterByValue($value, $group, $ns = "wgroup") {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}