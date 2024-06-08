<?php

namespace Wgroup\CertificateGrade;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CertificateGradeParticipant\CertificateGradeParticipant;

/**
 * Idea Model
 */
class CertificateGrade extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_certificate_grade';

    /*
     * Validation
     */
    public $rules = [

    ];

    public $belongsTo = [
        'program' => ['Wgroup\CertificateProgram\CertificateProgram', 'key' => 'certificate_program_id', 'otherKey' => 'id'],
    ];

    public $hasMany = [
        'calendar' => ['Wgroup\CertificateGradeCalendar\CertificateGradeCalendar'],
        'agents' => ['Wgroup\CertificateGradeAgent\CertificateGradeAgent'],
        'participants' => ['Wgroup\CertificateGradeParticipant\CertificateGradeParticipant', 'key' => 'certificate_grade_id', 'otherKey' => 'id'],
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
        return CertificateGradeParticipant::where('certificate_grade_id', $this->id)->count();
    }

    public function  getParticipants()
    {
        return CertificateGradeParticipant::where('certificate_grade_id', $this->id)->get();
    }

    public function updateParticipantCertificateExpiration()
    {
        $query = DB::table('wg_certificate_grade_participant');

        $query->join("wg_certificate_grade", function ($join) {
            $join->on('wg_certificate_grade.id', '=', 'wg_certificate_grade_participant.certificate_grade_id');

        })->join("wg_customers", function ($join) {
            $join->on('wg_customers.id', '=', 'wg_certificate_grade_participant.customer_id');

        })->join("wg_certificate_program", function ($join) {
            $join->on('wg_certificate_program.id', '=', 'wg_certificate_grade.certificate_program_id');

        })
            ->where('wg_certificate_grade_participant.hasCertificate', 1)
            ->update(
                [
                    'wg_certificate_grade_participant.certificateExpirationAt' => DB::raw("CASE WHEN wg_certificate_program.validityType = 'dias' THEN DATE_ADD(wg_certificate_grade_participant.certificateCreatedAt, INTERVAL wg_certificate_program.validityNumber DAY)
                                                                                            WHEN wg_certificate_program.validityType = 'meses' THEN DATE_ADD(wg_certificate_grade_participant.certificateCreatedAt, INTERVAL wg_certificate_program.validityNumber MONTH)
                                                                                            WHEN wg_certificate_program.validityType = 'meses' THEN DATE_ADD(wg_certificate_grade_participant.certificateCreatedAt, INTERVAL wg_certificate_program.validityNumber YEAR) END"),
                ]
            );
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
