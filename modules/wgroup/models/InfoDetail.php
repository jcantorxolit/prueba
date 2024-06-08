<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\CustomerParameter\CustomerParameterDTO;

/**
 * Town Model
 */
class InfoDetail extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_info_detail';

    /*
     * Validation
     */
    public $rules = [
        'entityId' => 'required',
        'entityName' => 'required',
        'type' => 'required'
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [

    ];

    /**
     * @var bool Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    public function  getType()
    {
        $type = $this->getParameterByValue($this->type, "extrainfo");

        if ($type == null && $this->type != '-S-') {
            $type = $this->getCustomerParametersByValue($this->type, "extraContactInformation");

            $instance = new \stdClass();

            if ($type != null && isset($type->group) && isset($type->value)) {
                $instance->id = $type->id;
                $instance->group = $type->group;
                $instance->item = $type->value;
                $instance->value = $type->id;
                $type = $instance;
            }
        }

        return $type;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    protected function getCustomerParametersByValue($value, $group, $ns = "wgroup")
    {
        $entity = CustomerParameter::find($value);
        if ($entity == null) {
            return null;
        }
        return CustomerParameterDTO::parse(CustomerParameter::find($value));
    }

}
