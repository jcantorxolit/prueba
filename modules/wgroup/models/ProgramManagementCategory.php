<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class ProgramManagementCategory extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_program_management_category';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];


    public function items()
    {
        return $this->hasMany('Wgroup\Models\ProgramManagementCategory', 'parent_id');
    }

    public function childContent()
    {
        return $this->hasMany('ProgramManagementCategory', 'parent_id', 'id');
    }

    public function parentContent()
    {
        return $this->hasOne('ProgramManagementCategory', 'id', 'parent_id');
    }

    public function  getProgram()
    {
        $sql = "SELECT
                `wg_program_management`.*,
                IFNULL(SUM(wg_program_management_question.weightedValue), 0) weightedValueTotal
            FROM
                `wg_program_management`
            INNER JOIN `wg_program_management_category` ON `wg_program_management_category`.`program_id` = `wg_program_management`.`id`
            LEFT JOIN `wg_program_management_question` ON `wg_program_management_question`.`category_id` = `wg_program_management_category`.`id`
            WHERE wg_program_management_category.id = {$this->id}
            GROUP BY `wg_program_management`.`id`
            ORDER BY wg_program_management.name";

        $program = DB::select($sql);

        return count($program) > 0 ? $program[0] : null;
    }

    public function  getStatus()
    {
        return $this->getParameterByValue($this->status, "config_workplace_status");
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
