<?php

namespace Wgroup\CertificateGradeParticipant;

use BackendAuth;
use Log;
use DB;
use MyProject\Proxies\__CG__\stdClass;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CertificateGradeParticipantDocument\CertificateGradeParticipantDocument;
use Wgroup\Models\CustomerDto;
use Wgroup\Models\InfoDetail;

/**
 * Idea Model
 */
class CertificateGradeParticipant extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_certificate_grade_participant';

    /*
     * Validation
     */
    public $rules = [

    ];

    public $belongsTo = [
        'grade' => ['Wgroup\CertificateGrade\CertificateGrade', 'key' => 'certificate_grade_id', 'otherKey' => 'id'],
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
        'countryOrigin' => ['RainLab\User\Models\Country', 'key' => 'country_origin_id', 'otherKey' => 'id'],
        'countryResidence' => ['RainLab\User\Models\Country', 'key' => 'country_residence_id', 'otherKey' => 'id'],
    ];

    public $hasMany = [

    ];

    public $attachOne = [
        'certificate' => ['System\Models\File'],
        'logo' => ['System\Models\File']
    ];

    public function  getCustomer()
    {
        $query = "select c.id, businessName item, c.id value, p.item arl
                    from wg_customers c
                    left join (
                                            select * from system_parameters
                                            where system_parameters.group = 'arl'
                                            ) p on c.arl = p.value
                    where c.id = :customer_id
                    order by businessName";

        $results = DB::select( $query, array(
            'customer_id' => $this->customer_id,
        ));

        return $results[0];
    }

    public function  getDocumentType(){
        return $this->getParameterByValue($this->documentType, "tipodoc");
    }

    public function  getWorkCenter(){
        return $this->getParameterByValue($this->workCenter, "certificate_grade_work_center");
    }

    public function  getChannel(){
        return $this->getParameterByValue($this->channel, "certificate_grade_channel");
    }

    public function getInfoDetail()
    {
        return InfoDetail::whereEntityname(get_class($this))->whereEntityid($this->id)->get();
    }

    public function getCountAttachment()
    {
        return CertificateGradeParticipantDocument::where("certificate_grade_participant_id", $this->id)->count();
    }

    public function getPrice()
    {
        $model = new \stdClass();
        $model->id = 0;
        $model->amount = $this->amount;

        return $model;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
