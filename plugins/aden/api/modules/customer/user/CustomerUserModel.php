<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\User;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerUserModel extends Model
{
    //use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_user";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
        'user' => ['October\Rain\Auth\Models\User', 'key' => 'user_id', 'otherKey' => 'id'],
    ];

    public $attachOne = [
        'cover' => ['System\Models\File'],
        'document' => ['System\Models\File'],
    ];

    public function getProfile()
    {
        return $this->getParameterByValue($this->user->wg_type, "wg_customer_user_profile");
    }

    public function getGender()
    {
        return $this->getParameterByValue($this->gender, "gender");
    }

    public function getType()
    {
        return $this->getParameterByValue($this->type, "agent_type");
    }

    public function getRole()
    {
        return $this->getParameterByValue($this->role, "customer_user_role");
    }

    public function getDocumentType()
    {
        return $this->getParameterByValue($this->documentType, "employee_document_type");
    }

    public function getCustomer()
    {
        $customer = DB::table('wg_customers')->where('id', $this->user->company)->first();
        return $customer ? [
            "id" => $customer->id,
            "value" => $customer->id,
            "item" => $customer->businessName,
            "relation" => ($relation = $this->getParameterByValue($this->relation, 'customer_user_relation')) ? $relation->item : 'NA',
            "relationCode" => $this->relation,
        ] : null;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
