<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

/**
 * Agent Model
 */
class Agent extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_agent';

    /*
     * Validation
     */
    public $rules = [
        'name' => 'required'
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];

    public $attachOne = [
        'logo' => ['System\Models\File'],
        'signature' => ['System\Models\File']
    ];

    public $hasMany = [
        'skills' => ['Wgroup\Models\AgentSkill', 'key' => 'agent_id', 'otherKey' => 'id'],
        'occupations' => ['Wgroup\Models\AgentOccupation', 'key' => 'agent_id', 'otherKey' => 'id'],
    ];

    public function scopeIsEnabled($query)
    {
        return $query->where('active', true);
    }

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "agent_type");
    }

    public function  getDocumentType()
    {
        return $this->getParameterByValue($this->documentType, "tipodoc");
    }

    public function  getGender()
    {
        return $this->getParameterByValue($this->gender, "gender");
    }

    public function  getLegalType()
    {
        return $this->getParameterByValue($this->legalType, "agent_legal_type");
    }

    public function  getRole()
    {
        return $this->getParameterByValue($this->role, "agent_role_list");
    }

    public function getInfoDetail()
    {
        return InfoDetail::whereEntityname(get_class($this))->whereEntityid($this->id)->get();
    }

    public static function getInfoDetailTable($entityId, $type)
    {
        $sql =  str_replace('\\', '\\\\',"SELECT MIN(`value`) `value`, entityId, entityName FROM wg_info_detail WHERE entityName = 'Wgroup\\Models\\Agent'
                        AND type = '$type'
                        AND entityId = $entityId
						GROUP BY entityId, entityName, type");

        $result = DB::select($sql);

        return count($result) > 0 ? $result[0] : null;
    }

    public static function getNameList()
    {
        if (self::$nameList)
            return self::$nameList;

        return self::$nameList = self::all()->lists('name', 'id');
    }

    /**
     * Returns the public image file path to this user's avatar.
     */
    public function getAvatarThumb($size = 25, $default = null)
    {
        if (!$default)
            $default = 'mm'; // Mystery man

        if ($this->logo)
            return $this->logo->getThumb($size, $size);
        else
            return '//www.gravatar.com/avatar/' . md5(strtolower(trim($this->documentNumber))) . '?s=' . $size . '&d=' . urlencode($default);
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

}
