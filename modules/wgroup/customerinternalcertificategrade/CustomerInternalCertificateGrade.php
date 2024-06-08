<?php

namespace Wgroup\CustomerInternalCertificateGrade;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerInternalCertificateGradeParticipant\CustomerInternalCertificateGradeParticipant;

/**
 * Idea Model
 */
class CustomerInternalCertificateGrade extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_internal_certificate_grade';

    /*
     * Validation
     */
    public $rules = [];

    public $belongsTo = [
        'program' => ['Wgroup\CustomerInternalCertificateProgram\CustomerInternalCertificateProgram', 'key' => 'customer_internal_certificate_program_id', 'otherKey' => 'id'],
    ];

    public $hasMany = [
        'calendar' => ['Wgroup\CustomerInternalCertificateGradeCalendar\CustomerInternalCertificateGradeCalendar'],
        'agents' => ['Wgroup\CustomerInternalCertificateGradeAgent\CustomerInternalCertificateGradeAgent'],
        'participants' => ['Wgroup\CustomerInternalCertificateGradeParticipant\CustomerInternalCertificateGradeParticipant', 'key' => 'customer_internal_certificate_grade_id', 'otherKey' => 'id'],
    ];

    public function  getStatus()
    {
        return $this->getParameterByValue($this->status, "certificate_grade_status");
    }

    public function  getLocation()
    {
        return $this->getParameterByValue($this->location, "certificate_grade_location");
    }

    public function  getParticipantsCount()
    {
        return CustomerInternalCertificateGradeParticipant::where('customer_internal_certificate_grade_id', $this->id)->count();
    }

    public function  getParticipants()
    {
        return DB::table('wg_customer_internal_certificate_grade_participant')
            ->join("wg_customer_internal_certificate_grade", function ($join) {
                $join->on("wg_customer_internal_certificate_grade.id", "=", "wg_customer_internal_certificate_grade_participant.customer_internal_certificate_grade_id");
            })
            ->join("wg_customer_employee", function ($join) {
                $join->on("wg_customer_employee.id", "=", "wg_customer_internal_certificate_grade_participant.customer_employee_id");
            })
            ->join("wg_employee", function ($join) {
                $join->on("wg_employee.id", "=", "wg_customer_employee.employee_id");
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_internal_certificate_grade_participant.customer_id');
            })
            ->select(
                "wg_customer_internal_certificate_grade_participant.id",
                "wg_customer_internal_certificate_grade_participant.isApproved",
                "wg_customer_internal_certificate_grade_participant.hasCertificate",
                "wg_employee.fullName",
                "wg_employee.documentNumber AS identificationNumber"
            )
            ->where("wg_customer_internal_certificate_grade.id", $this->id)
            ->get();
    }

    public function updteParticipant($entity)
    {
        DB::table('wg_customer_internal_certificate_grade_participant')
            ->where("wg_customer_internal_certificate_grade_participant.id", $entity->id)
            ->update([
                "validateCodeCertificate" => $entity->validateCodeCertificate,
                "generatedBy" => $entity->generatedBy,
                "certificateCreatedAt" => $entity->certificateCreatedAt,
                "hasCertificate" => $entity->hasCertificate,
            ]);
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
