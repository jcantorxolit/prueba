<?php

namespace Wgroup\CustomerHealthDamageQualificationSourceDiagnostic;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerHealthDamageQualificationSourceDiagnosticDocument\CustomerHealthDamageQualificationSourceDiagnosticDocument;
use Wgroup\CustomerHealthDamageQualificationSourceDiagnosticDocument\CustomerHealthDamageQualificationSourceDiagnosticDocumentDTO;
use Wgroup\CustomerHealthDamageQualificationSourceDiagnosticSupport\CustomerHealthDamageQualificationSourceDiagnosticSupport;
use Wgroup\CustomerHealthDamageQualificationSourceDiagnosticSupport\CustomerHealthDamageQualificationSourceDiagnosticSupportDTO;
use Wgroup\DisabilityDiagnostic\DisabilityDiagnostic;

/**
 * Idea Model
 */
class CustomerHealthDamageQualificationSourceDiagnostic extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_health_damage_qs_diagnostic';

    public $belongsTo = [
        'medicine' => ['Wgroup\WorkMedicine\WorkMedicine', 'key' => 'customer_work_medicine_id', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [

    ];

    public function  getIsActive()
    {
        return $this->isActive == 1;
    }

    public function  getSupports()
    {
        return CustomerHealthDamageQualificationSourceDiagnosticSupportDTO::parse(CustomerHealthDamageQualificationSourceDiagnosticSupport::whereCustomerHealthDamageQualificationSourceDiagnosticId($this->id)->get());
    }

    public function  getDocuments()
    {
        return CustomerHealthDamageQualificationSourceDiagnosticDocumentDTO::parse(CustomerHealthDamageQualificationSourceDiagnosticDocument::whereCustomerHealthDamageQualificationSourceDiagnosticId($this->id)->get());
    }

    public function  getDiagnostic()
    {
        return $this->getParameterByValue($this->diagnostic, "work_health_damage_diagnostic");
    }

    public function  getLaterality()
    {
        return $this->getParameterByValue($this->laterality, "work_health_damage_laterality");
    }

    public function  getEntityPerformsDiagnostic()
    {
        return $this->getParameterByValue($this->entityPerformsDiagnostic, "work_health_damage_entity_perform_diagnostic");
    }

    public function  getCodeCIE10()
    {
        return DisabilityDiagnostic::find($this->codeCIE10);
    }

    public function  getApplicant()
    {
        return $this->getParameterByValue($this->applicant, "work_health_damage_applicant");
    }

    public function  getDirectorApt()
    {
        return $this->getParameterByValue($this->directorApt, "work_health_damage_apt");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
