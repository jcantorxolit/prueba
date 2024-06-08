<?php

namespace Wgroup\ProjectTaskType;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\Models\InfoDetail;

/**
 * Agent Model
 */
class ProjectTaskType extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_project_task_type';

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
    ];

    public $hasMany = [

    ];

    public function scopeIsEnabled($query)
    {
        return $query->where('active', true);
    }

    public function  getProfession(){
        return $this->getParameterByValue($this->profession, "employee_profession");
    }

    public function  getDocumentType()
    {
        return $this->getParameterByValue($this->documentType, "employee_document_type");
    }

    public function  getGender()
    {
        return $this->getParameterByValue($this->gender, "gender");
    }

    public function  getAfp(){
        return $this->getParameterByValue($this->afp, "afp");
    }

    public function  getArl(){
        return $this->getParameterByValue($this->arl, "arl");
    }

    public function  getEps(){
        return $this->getParameterByValue($this->eps, "eps");
    }

    public function getInfoDetail()
    {
        return InfoDetail::whereEntityname(get_class($this))->whereEntityid($this->id)->get();
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
