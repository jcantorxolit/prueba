<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Intervention;

use AdeN\Api\Modules\Customer\CustomerModel;
use Illuminate\Support\Facades\DB;
use October\Rain\Database\Model;

class InterventionModel extends Model
{
    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_job_condition_self_evaluation_answer_interventions";


    public $attachMany = [
        'documents' => [ 'System\Models\File' ]
    ];

    public function getResponsible() {
        $qAgentUser = CustomerModel::getRelatedAgentAndUser('agent_user');

        return $this->query()
            ->join('wg_customer_job_condition_self_evaluation_answers as ans', 'ans.id', '=', 'wg_customer_job_condition_self_evaluation_answer_interventions.self_evaluation_answer_id')
            ->join('wg_customer_job_condition_self_evaluation as eva', 'eva.id', '=', 'ans.self_evaluation_id')
            ->join('wg_customer_job_condition as jc', 'jc.id', '=', 'eva.job_condition_id')
            ->join(DB::raw("({$qAgentUser})"), function ($join) {
                $join->on('agent_user.customer_id', '=', 'jc.customer_id');
                $join->on('agent_user.type', '=', 'wg_customer_job_condition_self_evaluation_answer_interventions.responsible_type');
                $join->on('agent_user.id', '=', 'wg_customer_job_condition_self_evaluation_answer_interventions.responsible_id');
            })
            ->where('wg_customer_job_condition_self_evaluation_answer_interventions.id', $this->id)
            ->select( 'agent_user.id', 'agent_user.type', 'agent_user.name', 'agent_user.email' )
            ->first();
    }
}
