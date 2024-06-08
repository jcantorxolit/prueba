<?php

namespace Wgroup\ResourceLibrary;

use BackendAuth;
use DB;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class ResourceLibrary extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_resource_library';

    public $belongsTo = [

    ];

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $attachOne = [
        'document' => ['System\Models\File'],
        'cover' => ['System\Models\File'],
    ];

    public $hasMany = [

    ];

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "resource_library_type");
    }

    public function getKeywords()
    {
        if ($this->keyword == null || $this->keyword == '') {
            return array();
        }

        $keywordList = explode(',', $this->keyword);

        $keywords = array();

        foreach($keywordList as $keyword) {
            $kw = new \stdClass();
            $kw->text = $keyword;
            $keywords[] = $kw;
        }

        return $keywords;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
