<?php

namespace Wgroup\Models;

use AdeN\Api\Modules\Customer\CustomerModel;
use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerContractor\CustomerContractor;
use Wgroup\CustomerEmployee\CustomerEmployee;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\CustomerParameter\CustomerParameterDTO;
use DB;
use Wgroup\CustomerUser\CustomerUser;
use Wgroup\InvestigationAlEconomicActivity\InvestigationAlEconomicActivity;
use Wgroup\NephosIntegrationProductPlan\NephosIntegrationProductPlan;
use Wgroup\NephosIntegrationProductPlan\NephosIntegrationProductPlanDTO;
use Wgroup\NephosIntegrationProductPlanFeature\NephosIntegrationProductPlanFeature;
use Wgroup\NephosIntegrationProductPlanFeature\NephosIntegrationProductPlanFeatureDTO;

/**
 * Idea Model
 */
class Customer extends Model
{

    /**
     * @var array Cache for nameList() method
     */
    protected static $nameList = null;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customers';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
        'country' => ['RainLab\User\Models\Country', 'key' => 'country_id', 'otherKey' => 'id'],
        'state' => ['RainLab\User\Models\State', 'key' => 'state_id', 'otherKey' => 'id'],
        'town' => ['Wgroup\Models\Town', 'key' => 'city_id', 'otherKey' => 'id'],
        'group' => ['RainLab\User\Models\UserGroup', 'key' => 'group_id', 'otherKey' => 'id']
    ];

    public $hasMany = [
        'maincontacts' => ['Wgroup\Models\Contact'],
        'unities' => ['Wgroup\Models\CustomerAgent'],
        'diagnostics' => ['Wgroup\Models\CustomerDiagnostic']
    ];

    public $attachOne = [
        'logo' => ['System\Models\File']
    ];

    public function  getStatus()
    {
        return $this->getParameterByValue($this->status, "estado");
    }

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "tipocliente");
    }

    public function  getDocumentType()
    {
        return $this->getParameterByValue($this->documentType, "tipodoc");
    }

    public function  getClassification()
    {
        return $this->getParameterByValue($this->classification, "customer_classification");
    }

    public function  getSize()
    {
        return $this->getParameterByValue($this->size, "wg_customer_size");
    }

    public function  getArl()
    {
        return $this->getParameterByValue($this->arl, "arl");
    }

    public function  getTotalEmployee()
    {
        return $this->getParameterByValue($this->totalEmployee, "wg_customer_employee_number");
    }

    public function  getRiskLevel()
    {
        return $this->getParameterByValue($this->riskLevel, "wg_customer_risk_level");
    }

    public function  getRiskClass()
    {
        return $this->getParameterByValue($this->riskClass, "wg_customer_risk_class");
    }

    public function getEmployeeDocumentTypes()
    {

        $customerId = $this->id;

        $query = "Select * from
wg_customer_parameter
where namespace = 'wgroup' and `group` = 'employeeDocumentType' and customer_id = :customer_id
union all
select `value`, $customerId customer_id, namespace, `group`, `value`, item, '' `data`, 1 isVisible, NOW() from
system_parameters
where namespace = 'wgroup' and `group` = 'wg_employee_attachment'";

        $whereArray = array();

        $whereArray["customer_id"] = $customerId;

        $results = DB::select($query, $whereArray);

        return $results;

        //return $this->getCustomerParametersByValue($this->id, "employeeDocumentType");
    }

    public function getContactTypes()
    {

        $customerId = $this->id;

        $query = "SELECT
	*
FROM
	system_parameters
WHERE
	namespace = 'wgroup'
AND `group` = 'rolescontact'
UNION ALL
	SELECT
		id,
		namespace,
		wg_customer_parameter.`group`,
		wg_customer_parameter.`value` item,
		id `value`,
		'' c
	FROM
		wg_customer_parameter
	WHERE
		namespace = 'wgroup'
	AND `group` = 'contactTypes'
	AND customer_id = :customer_id";

        $whereArray = array();

        $whereArray["customer_id"] = $customerId;

        $results = DB::select($query, $whereArray);

        return $results;

        //return $this->getCustomerParametersByValue($this->id, "employeeDocumentType");
    }

    public function getCustomerDocumentTypes()
    {

        $customerId = $this->id;

        $query = "Select * from
wg_customer_parameter
where namespace = 'wgroup' and `group` = 'customerDocumentType' and customer_id = :customer_id
union all
select `value`, $customerId customer_id, namespace, `group`, `value`, item, '' `data`, 1 isVisible, NOW() from
system_parameters
where namespace = 'wgroup' and `group` = 'customer_document_type'";

        $whereArray = array();

        $whereArray["customer_id"] = $customerId;

        $results = DB::select($query, $whereArray);

        return $results;

        //return $this->getCustomerParametersByValue($this->id, "employeeDocumentType");
    }

    public function getExtraContactInformationList()
    {

        $customerId = $this->id;

        $query = "
select * from
system_parameters
where namespace = 'wgroup' and `group` = 'extrainfo'
union all
Select id, namespace, `group`, `value` item, id, '' `code` from
wg_customer_parameter
where namespace = 'wgroup' and `group` = 'extraContactInformation' and customer_id = :customer_id";

        $whereArray = array();

        $whereArray["customer_id"] = $customerId;

        $results = DB::select($query, $whereArray);

        return $results;

        //return $this->getCustomerParametersByValue($this->id, "employeeDocumentType");
    }

    public function getEmployeeDocumentTypesList()
    {
        return $this->getCustomerParametersByValue($this->id, "employeeDocumentType");
    }

    public function getCustomerDocumentTypesList()
    {
        return $this->getCustomerParametersByValue($this->id, "customerDocumentType");
    }

    public function getExtraContactInformation()
    {
        return $this->getCustomerParametersByValue($this->id, "extraContactInformation");
    }

    public function getContactTypeList()
    {
        return $this->getCustomerParametersByValue($this->id, "contactTypes");
    }

    public function getProjectTypes()
    {
        return $this->getCustomerParametersByValue($this->id, "projectType");
    }

    public function getProjectTaskTypes()
    {
        return $this->getCustomerParametersByValue($this->id, "projectTaskType");
    }

    public function getUserSkills()
    {
        return $this->getCustomerParametersByValue($this->id, "userSkill");
    }

    public function getEconomicGroupAssignedHours()
    {
        return $this->getCustomerParametersByValue($this->id, "economicGroupAssignedHours");
    }

    public function getContractorTypes()
    {
        return $this->getCustomerParametersByValue($this->id, "contractorTypes");
    }

    public function usersNotification()
    {
        return  array_map(function ($item) {
            $item->value = (new CustomerModel)->findAgentAndUserRaw($this->id, $item->item, $item->value);
            return $item;
        }, $this->getCustomerParametersByValue($this->id, "userNotification"));
    }

    public function getOfficeTypeMatrixSpecialList()
    {
        return  $this->getCustomerParametersByValue($this->id, "officeTypeMatrixSpecial");
    }

    public function getBusinessUnitMatrixSpecialList()
    {
        return  $this->getCustomerParametersByValue($this->id, "businessUnitMatrixSpecial");
    }

    public function infoDetail()
    {
        return InfoDetail::whereEntityname(get_class($this))->whereEntityid($this->id)->get();
    }

    public function getUnities()
    {
        return $this->unities()->groupBy("type")->get();
    }

    public function getResource()
    {
        $nephos = $this->getNephos();

        $resource = new \stdClass();
        $resource->plan = $this->getProductPlan();
        $resource->features = [];
        $resource->instanceId = $this->instance_id;
        $resource->isDisable = $this->is_disable == 1;
        $resource->isEnable = $this->is_enable == 1;
        $resource->isRemove = $this->is_Remove == 1;

        if ($resource->plan != null) {
            $userQuantity = $nephos ? $nephos->users : $resource->plan->features[0]->min;
            $contractorQuantity = $nephos ? $nephos->contractors : $resource->plan->features[1]->min;
            $diskQuantity = $nephos ? $nephos->disk : $resource->plan->features[2]->min;
            $employeeQuantity = $nephos ? $nephos->employees : $resource->plan->features[3]->min;

            $userMaxAllowed = $resource->plan->features[0]->max;
            $contractorMaxAllowed = $resource->plan->features[1]->max;
            $diskMaxAllowed = $resource->plan->features[2]->max;
            $employeeMaxAllowed = $resource->plan->features[3]->max;

            $employee = new \stdClass();
            $employee->quantity = $this->getEmployeeCount();
            $employee->max = $employeeQuantity;
            $employee->allowed = $employeeMaxAllowed;
            $employee->avg = round(($employee->quantity * 100) / $employeeQuantity, 2);

            $contractor = new \stdClass();
            $contractor->quantity = $this->getContractorCount();
            $contractor->max = $contractorQuantity;
            $contractor->allowed = $contractorMaxAllowed;
            $contractor->avg = round(($contractor->quantity * 100) / $contractorQuantity, 2);;

            $user = new \stdClass();
            $user->quantity = $this->getUserCount();
            $user->max = $userQuantity;
            $user->allowed = $userMaxAllowed;
            $user->avg = round(($user->quantity * 100) / $userQuantity, 2);;;

            $disk = new \stdClass();
            $disk->quantity = $this->getDiskFileSizeCount();
            $disk->max = $diskQuantity;
            $disk->allowed = $diskMaxAllowed;
            $disk->avg = round(($disk->quantity * 100) / $diskQuantity, 2);

            $resource->features["employee"] = $employee;
            $resource->features["contractor"] = $contractor;
            $resource->features["user"] = $user;
            $resource->features["disk"] = $disk;
        }

        return $resource;
    }

    private function getEmployeeCount()
    {
        return CustomerEmployee::whereCustomerId($this->id)->where('isActive', 1)->count();
    }

    private function getContractorCount()
    {
        return CustomerContractor::whereCustomerId($this->id)->count();
    }

    private function getUserCount()
    {
        return CustomerUser::whereCustomerId($this->id)->count();
    }

    public function getEconomicActivity()
    {
        return InvestigationAlEconomicActivity::find($this->economicActivity);
    }

    private function getProductPlan()
    {
        if ($this->plan_id != null) {
            return NephosIntegrationProductPlanDTO::parse(NephosIntegrationProductPlan::find($this->plan_id));
        } else {
            return null;
        }
    }

    public function getPlans()
    {
        $plans = NephosIntegrationProductPlan::whereIsActive(1)->get();

        foreach ($plans as $plan) {
            $plan->featureList = NephosIntegrationProductPlanFeatureDTO::parse(NephosIntegrationProductPlanFeature::whereProductPlanId($plan->id)->get());
        }

        return $plans;
    }

    public static function getNameList()
    {
        if (self::$nameList)
            return self::$nameList;

        return self::$nameList = [];
        //return self::$nameList = self::all()->lists('businessName', 'id');
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

    protected function getCustomerParametersByValue($value, $group, $ns = "wgroup")
    {
        return CustomerParameterDTO::parse(CustomerParameter::whereNamespace($ns)->whereGroup($group)->whereCustomerId($value)->get());
    }

    public static function getRelatedCustomers($id)
    {
        $query = "SELECT customer_id customerId
FROM
  ( SELECT parent_id,
           customer_id
   FROM wg_customer_economic_group
   UNION ALL SELECT customer_id,
                    contractor_id
   FROM wg_customer_contractor ) p
WHERE parent_id = :customer_id
GROUP BY customer_id";

        $results = DB::select($query, array(
            'customer_id' => $id
        ));

        return $results;
    }

    public static function getRelatedAgentAndUser()
    {
        return "( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible";
    }

    public static function getAgentsAndUsers($id)
    {
        $query = "SELECT DISTINCT * FROM
        ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
		SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id
        WHERE c.isActive = 1) responsible
		WHERE customer_id = :customer_id ";

        $results = DB::select($query, array(
            'customer_id' => $id
        ));

        return $results;
    }

    public static function getAgentOrUser($id, $type)
    {
        $query = "SELECT DISTINCT * FROM
        ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
		SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) responsible
		WHERE id = :id AND type = :type ";

        $results = DB::select($query, array(
            'id' => $id,
            'type' => $type
        ));

        return count($results) > 0 ? $results[0] : null;
    }

    private function getDiskFileSizeCount()
    {
        $customerId = $this->id;
        $query = "SELECT attachment_id, SUM(file_size) file_size FROM
(

SELECT attachment_id, SUM(file_size) file_size FROM system_files
WHERE attachment_type = 'Wgroup\\\Models\\\Customer' AND attachment_id = $customerId
GROUP BY attachment_id

UNION ALL

SELECT attachment_id, SUM(file_size) file_size FROM system_files
WHERE attachment_type = 'Wgroup\\\Models\\\Customer' AND attachment_id = $customerId
GROUP BY attachment_id

UNION ALL

SELECT c.id, SUM(file_size) file_size
FROM wg_customers c
INNER JOIN wg_customer_document cd on c.id = cd.customer_id
INNER JOIN system_files sf ON cd.id = sf.attachment_id
WHERE attachment_type = 'Wgroup\\\Models\\\CustomerDocument' AND c.id = $customerId
GROUP BY c.id

UNION ALL

SELECT c.id, SUM(file_size) file_size
FROM wg_customers c
INNER JOIN wg_customer_employee ce on c.id = ce.customer_id
INNER JOIN wg_customer_employee_document ed on ce.id = ed.customer_employee_id
INNER JOIN system_files sf ON ed.id = sf.attachment_id
WHERE attachment_type = 'Wgroup\\\CustomerEmployeeDocument\\\CustomerEmployeeDocument' AND c.id = $customerId
GROUP BY c.id

UNION ALL

SELECT c.id, SUM(file_size) file_size
FROM wg_customers c
INNER JOIN wg_customer_employee ce on c.id = ce.customer_id
INNER JOIN wg_customer_absenteeism_disability ad on ce.id = ad.customer_employee_id
INNER JOIN wg_customer_absenteeism_disability_document dd on ad.id = dd.customer_disability_id
INNER JOIN system_files sf ON dd.id = sf.attachment_id
WHERE attachment_type = 'Wgroup\\\CustomerAbsenteeismDisabilityDocument\\\CustomerAbsenteeismDisabilityDocument' AND c.id = $customerId
GROUP BY c.id

UNION ALL

SELECT c.id, SUM(file_size) file_size
FROM wg_customers c
INNER JOIN wg_customer_contractor cc on c.id = cc.customer_id
INNER JOIN wg_customer_contract_detail cd on cc.id = cd.contractor_id
INNER JOIN wg_customer_contract_detail_document dd on cd.id = dd.customer_contract_detail_id
INNER JOIN system_files sf ON dd.id = sf.attachment_id
WHERE attachment_type = 'Wgroup\\\CustomerContractDetailDocument\\\CustomerContractDetailDocument' AND c.id = $customerId
GROUP BY c.id

UNION ALL

SELECT c.id, SUM(file_size) file_size
FROM wg_customers c
INNER JOIN wg_customer_diagnostic_prevention_document pd on c.id = pd.customer_id
INNER JOIN system_files sf ON pd.id = sf.attachment_id
WHERE attachment_type = 'Wgroup\\\CustomerDiagnosticPreventionDocument\\\CustomerDiagnosticPreventionDocument' AND c.id = $customerId
GROUP BY c.id

UNION ALL

SELECT c.id, SUM(file_size) file_size
FROM wg_customers c
INNER JOIN wg_customer_employee ce on c.id = ce.customer_id
INNER JOIN wg_customer_health_damage_restriction dr on ce.id = dr.customer_employee_id
INNER JOIN wg_customer_health_damage_restriction_detail drd on dr.id = drd.customer_health_damage_restriction_id
INNER JOIN system_files sf ON drd.id = sf.attachment_id
WHERE attachment_type = 'Wgroup\\\CustomerHealthDamageRestrictionDetail\\\CustomerHealthDamageRestrictionDetail' AND c.id = $customerId
GROUP BY c.id

UNION ALL

SELECT c.id, SUM(file_size) file_size
FROM wg_customers c
INNER JOIN wg_certificate_grade_participant cgp on c.id = cgp.customer_id
INNER JOIN wg_certificate_grade_participant_document cgpd on cgp.id = cgpd.certificate_grade_participant_id
INNER JOIN system_files sf ON cgpd.id = sf.attachment_id
WHERE attachment_type = 'Wgroup\\\CertificateGradeParticipantDocument\\\CertificateGradeParticipantDocument' AND c.id = $customerId
GROUP BY c.id

UNION ALL

SELECT c.id, SUM(file_size) file_size
FROM wg_customers c
INNER JOIN wg_certificate_external ce on c.id = ce.customer_id
INNER JOIN system_files sf ON ce.id = sf.attachment_id
WHERE attachment_type = 'Wgroup\\\CertificateExternal\\\CertificateExternal' AND c.id = $customerId
GROUP BY c.id

UNION ALL

-- TBC
SELECT c.id, SUM(file_size) file_size
FROM wg_customers c
INNER JOIN wg_customer_config_job ccj on c.id = ccj.customer_id
INNER JOIN wg_customer_config_job_activity ccja on ccj.id = ccja.job_id
INNER JOIN wg_customer_config_job_activity_document ccjad on ccja.id = ccjad.job_activity_id
INNER JOIN system_files sf ON ccjad.id = sf.attachment_id
WHERE attachment_type = 'Wgroup\\\CertificateGradeParticipantDocument\\\CertificateGradeParticipantDocument' AND c.id = $customerId
GROUP BY c.id

) fl ";

        $results = DB::select($query);

        $fileSize = count($results) > 0 ? $results[0]->file_size : 0;

        if ($fileSize != 0) {
            $fileSize = round(((($fileSize / 1024) / 1024) / 1024), 2);
        }

        return $fileSize;
    }

    private function getNephos()
    {
        $query = "SELECT
	*
FROM
	(
		SELECT
			n.customer_id,
			SUM(n.users) users,
			SUM(n.contractors) contractors,
			SUM(n.disk) disk,
			SUM(n.employees) employees
		FROM
			wg_nephos_customer_tracking n
		LEFT JOIN wg_customers c ON n.customer_id = c.id
		LEFT JOIN wg_product_plan pp ON pp.id = n.plan_id
		WHERE
			action IN ('install', 'configure')
		GROUP BY
			n.customer_id
	) p
where p.customer_id = " . $this->id;

        $results = DB::select($query);

        return count($results) > 0 ? $results[0] : null;;
    }

    public function getInfoDetailTable($type)
    {
        $entityId = $this->id;
        $sql =  str_replace('\\', '\\\\', "SELECT MIN(`value`) `value`, entityId, entityName FROM wg_info_detail WHERE entityName = 'Wgroup\\Models\\Customer'
                        AND type = '$type'
                        AND entityId = $entityId
						GROUP BY entityId, entityName, type");

        $result = DB::select($sql);

        return count($result) > 0 ? $result[0] : null;
    }

    public function attentionLines()
    {
        $classification = $this->getClassification();
        if ($classification && $classification->code == "NOCLIENT") {
            return Parameters::whereNamespace("wgroup")->whereGroup("attention_lines_no_client")->orderBy("value")->get();
        } elseif ($classification && (is_null($classification->code) || $classification->code != "NOCLIENT")) {
            return Parameters::whereNamespace("wgroup")->whereGroup("attention_lines_client")->orderBy("value")->get();
        }

        return [];
    }

    public function experienceVR()
    {
        return  array_map(function ($item) {
            $item->value = Parameters::find($item->item)->toArray();
            $item->value["type"] = $item->value["item"];
            return $item;
        }, $this->getCustomerParametersByValue($this->id, "experienceVR"));
    }
}
