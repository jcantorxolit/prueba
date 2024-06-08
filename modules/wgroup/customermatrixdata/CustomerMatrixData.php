<?php

namespace Wgroup\CustomerMatrixData;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerConfigActivity\CustomerConfigActivity;
use Wgroup\CustomerMatrixActivity\CustomerMatrixActivity;
use Wgroup\CustomerMatrixActivity\CustomerMatrixActivityDTO;
use Wgroup\CustomerMatrixDataControl\CustomerMatrixDataControl;
use Wgroup\CustomerMatrixDataControl\CustomerMatrixDataControlDTO;
use Wgroup\CustomerMatrixDataResponsible\CustomerMatrixDataResponsible;
use Wgroup\CustomerMatrixDataResponsible\CustomerMatrixDataResponsibleDTO;
use Wgroup\CustomerMatrixEnvironmentalAspect\CustomerMatrixEnvironmentalAspect;
use Wgroup\CustomerMatrixEnvironmentalAspect\CustomerMatrixEnvironmentalAspectDTO;
use Wgroup\CustomerMatrixEnvironmentalImpact\CustomerMatrixEnvironmentalImpact;
use Wgroup\CustomerMatrixEnvironmentalImpact\CustomerMatrixEnvironmentalImpactDTO;
use Wgroup\CustomerMatrixProject\CustomerMatrixProject;
use Wgroup\CustomerMatrixProject\CustomerMatrixProjectDTO;

/**
 * Idea Model
 */
class CustomerMatrixData extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_matrix_data';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'matrix' => ['Wgroup\CustomerMatrix\CustomerMatrix', 'key' => 'customer_matrix_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];

    public function getControls()
    {
        return CustomerMatrixDataControlDTO::parse(CustomerMatrixDataControl::whereCustomerMatrixDataId($this->id)->get());
    }

    public function getResponsible()
    {
        return CustomerMatrixDataResponsibleDTO::parse(CustomerMatrixDataResponsible::whereCustomerMatrixDataId($this->id)->get());
    }

    public function getCustomerMatrixProject()
    {
        return CustomerMatrixProject::find($this->customer_matrix_project_id);
    }

    public function getCustomerMatrixActivity()
    {
        return CustomerMatrixActivity::find($this->customer_matrix_activity_id);
    }

    public function getCustomerMatrixEnvironmentalAspect()
    {
        return CustomerMatrixEnvironmentalAspectDTO::parse(CustomerMatrixEnvironmentalAspect::find($this->customer_matrix_environmental_aspect_id));
    }

    public function getCustomerMatrixEnvironmentalImpact()
    {
        return CustomerMatrixEnvironmentalImpact::find($this->customer_matrix_environmental_impact_id);
    }

    public function getEnvironmentalImpactIn()
    {
        return $this->getParameterByValue($this->environmental_impact_in, 'matrix_environmental_impact_in');
    }

    public function getEnvironmentalImpactEx()
    {
        return $this->getParameterByValue($this->environmental_impact_ex, 'matrix_environmental_impact_ex');
    }

    public function getEnvironmentalImpactPr()
    {
        return $this->getParameterByValue($this->environmental_impact_pr, 'matrix_environmental_impact_pr');
    }

    public function getEnvironmentalImpactRe()
    {
        return $this->getParameterByValue($this->environmental_impact_re, 'matrix_environmental_impact_re');
    }

    public function getEnvironmentalImpactRv()
    {
        return $this->getParameterByValue($this->environmental_impact_rv, 'matrix_environmental_impact_rv');
    }

    public function getEnvironmentalImpactSe()
    {
        return $this->getParameterByValue($this->environmental_impact_se, 'matrix_environmental_impact_se');
    }

    public function getEnvironmentalImpactFr()
    {
        return $this->getParameterByValue($this->environmental_impact_fr, 'matrix_environmental_impact_fr');
    }

    public function getLegalImpactE()
    {
        return $this->getParameterByValue($this->legal_impact_e, 'matrix_legal_impact_e');
    }

    public function getLegalImpactC()
    {
        return $this->getParameterByValue($this->legal_impact_c, 'matrix_legal_impact_c');
    }

    public function getInterestedPartAc()
    {
        return $this->getParameterByValue($this->interested_part_ac, 'matrix_interested_part_ac');
    }

    public function getInterestedPartGe()
    {
        return $this->getParameterByValue($this->interested_part_ge, 'matrix_interested_part_ge');
    }

    public function getNature()
    {
        return $this->getParameterByValue($this->nature, 'matrix_nature');
    }

    public function getEmergencyConditionIn()
    {
        return $this->getParameterByValue($this->emergency_condition_in, 'matrix_environmental_impact_in');
    }

    public function getEmergencyConditionEx()
    {
        return $this->getParameterByValue($this->emergency_condition_ex, 'matrix_environmental_impact_ex');
    }

    public function getEmergencyConditionPr()
    {
        return $this->getParameterByValue($this->emergency_condition_pr, 'matrix_environmental_impact_pr');
    }

    public function getEmergencyConditionRe()
    {
        return $this->getParameterByValue($this->emergency_condition_re, 'matrix_environmental_impact_re');
    }

    public function getEmergencyConditionRv()
    {
        return $this->getParameterByValue($this->emergency_condition_rv, 'matrix_environmental_impact_rv');
    }

    public function getEmergencyConditionSe()
    {
        return $this->getParameterByValue($this->emergency_condition_se, 'matrix_environmental_impact_se');
    }

    public function getEmergencyConditionFr()
    {
        return $this->getParameterByValue($this->emergency_condition_fr, 'matrix_environmental_impact_fr');
    }

    public function getScope()
    {
        return $this->getParameterByValue($this->scope, 'matrix_scope');
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
