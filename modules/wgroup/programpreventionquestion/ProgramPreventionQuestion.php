<?php

namespace Wgroup\ProgramPreventionQuestion;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\Models\ProgramPrevention;
use Wgroup\Models\ProgramPreventionCategory;
use Wgroup\Models\ProgramPreventionCategoryDTO;

/**
 * Idea Model
 */
class ProgramPreventionQuestion extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_progam_prevention_question';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public function  getStatus()
    {
        return $this->getParameterByValue($this->status, "config_workplace_status");
    }

    public function getCategory()
    {
        return ProgramPreventionCategory::find($this->category_id);
    }

    public function getProgram($id)
    {
        return ProgramPrevention::find($id);
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

}
