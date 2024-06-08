<?php

namespace Wgroup\CertificateExternal;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CertificateExternal extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_certificate_external';

    public $belongsTo = [
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $attachOne = [
        'document' => ['System\Models\File']
    ];

    public function  getDocumentType(){
        return $this->getParameterByValue($this->documentType, "tipodoc");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
