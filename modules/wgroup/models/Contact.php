<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\CustomerParameter\CustomerParameterDTO;
use Wgroup\SystemParameter\SystemParameter;

/**
 * Town Model
 */
class Contact extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_contact';

    /*
     * Validation
     */
    public $rules = [
        'name' => 'required',
        'role' => 'required',
        'customer_id' => 'required'
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [

    ];

    public function infoDetail()
    {
        return InfoDetail::whereEntityname(get_class($this))->whereEntityid($this->id)->get();
    }

    public function  getRole()
    {
        $role = $this->getParameterByValue($this->role, "rolescontact");
        if ($role == null  && $this->role != '-S-') {
            $role = CustomerParameterDTO::getData(CustomerParameter::whereid($this->role)->first());
        }
        return $role;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }


}
