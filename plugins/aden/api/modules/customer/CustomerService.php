<?php

namespace AdeN\Api\Modules\Customer;

use AdeN\Api\Classes\BaseService;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Log;
use Str;
use Wgroup\SystemParameter\SystemParameter;

class CustomerService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function findByDocument($criteria)
    {
        $data = DB::table('wg_customers')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc')), function ($join) {
                $join->on('wg_customers.documentType', '=', 'tipodoc.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('tipocliente')), function ($join) {
                $join->on('wg_customers.type', '=', 'tipocliente.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_classification')), function ($join) {
                $join->on('wg_customers.classification', '=', 'customer_classification.value');
            })
            ->leftjoin("wg_investigation_economic_activity", function ($join) {
                $join->on('wg_investigation_economic_activity.id', '=', 'wg_customers.economicActivity');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('arl')), function ($join) {
                $join->on('arl.value', '=', 'wg_customers.arl');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_employee_number')), function ($join) {
                $join->on('wg_customer_employee_number.value', '=', 'wg_customers.totalEmployee');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_risk_class')), function ($join) {
                $join->on('wg_customer_risk_class.value', '=', 'wg_customers.riskClass');
            })
            ->leftjoin("rainlab_user_countries", function ($join) {
                $join->on('rainlab_user_countries.id', '=', 'wg_customers.country_id');
            })
            ->leftjoin("rainlab_user_states", function ($join) {
                $join->on('rainlab_user_states.id', '=', 'wg_customers.state_id');
            })
            ->leftjoin("wg_towns", function ($join) {
                $join->on('wg_towns.id', '=', 'wg_customers.city_id');
            })
            ->select(
                'wg_customers.id',
                'wg_customers.businessName',
                'wg_customers.directEmployees',
                'wg_customers.webSite',
                'tipodoc.value as documentTypeValue',
                'tipodoc.item as documentTypeText',
                'tipocliente.value as typeValue',
                'tipocliente.item as typeText',
                'customer_classification.value as classificationValue',
                'customer_classification.item as classificationText',
                'wg_customer_risk_class.value as riskClassValue',
                'wg_customer_risk_class.item as riskClassText',
                'wg_customer_employee_number.value as totalEmployeeValue',
                'wg_customer_employee_number.item as totalEmployeeText',
                'arl.value as arlValue',
                'arl.item as arlText',
                'wg_investigation_economic_activity.id as economicActivityId',
                'wg_investigation_economic_activity.name as economicActivityName',

                'rainlab_user_countries.id as countryId',
                'rainlab_user_countries.name as countryName',

                'rainlab_user_states.id as stateId',
                'rainlab_user_states.name as stateName',

                'wg_towns.id as cityId',
                'wg_towns.name as cityName'
            )
            ->where('wg_customers.documenttype', $criteria->documentType)
            ->where(function ($query) use ($criteria) {
                $query->where('documentNumber', $criteria->documentNumber)
                    ->orWhere('documentNumber', $criteria->documentNumber . $criteria->checkDigit)
                    ->orWhere('documentNumber', $criteria->documentNumber . '-' . $criteria->checkDigit);
            })
            ->get();

        return (new Collection($data))->map(function ($item) use ($criteria) {
            return [
                "id" => $item->id,
                "businessName" => $item->businessName,
                "documentNumber" => $criteria->documentNumber,
                "checkDigit" => $criteria->checkDigit,
                "directEmployeeNumber" => $item->directEmployees,
                "webSite" => $item->webSite,
                "documentType" => [
                    "value" => $item->documentTypeValue,
                    "item" => $item->documentTypeText
                ],
                "type" => [
                    "value" => $item->typeValue,
                    "item" => $item->typeText
                ],
                "arl" => [
                    "value" => $item->arlValue,
                    "item" => $item->arlText
                ],
                "riskClass" => [
                    "value" => $item->riskClassValue,
                    "item" => $item->riskClassText
                ],
                "classification" => [
                    "value" => $item->classificationValue,
                    "item" => $item->classificationText
                ],
                "totalEmployee" => [
                    "value" => $item->totalEmployeeValue,
                    "item" => $item->totalEmployeeText
                ],
                "economicActivity" => [
                    "id" => $item->economicActivityId,
                    "name" => $item->economicActivityName
                ],
                "country" => [
                    "id" => $item->countryId,
                    "name" => $item->countryName
                ],
                "state" => [
                    "id" => $item->stateId,
                    "name" => $item->stateName
                ],
                "city" => [
                    "id" => $item->cityId,
                    "name" => $item->cityName
                ],
            ];
        })->first();
    }

    public function getDocumentTypeList($customerId)
    {
        return DB::table('wg_customers')
            ->join(DB::raw(CustomerModel::getDocumentTypeRelation('document_type')), function ($join) {

                $join->on('wg_customers.id', '=', 'document_type.customer_id')
                    ->whereNull('document_type.customer_id', 'or');
            })
            ->select('document_type.*')
            ->where('wg_customers.id', $customerId)
            ->orderBy('document_type.item')
            ->get();
    }

    public function getEmployeeDocumentTypeList($customerId)
    {
        $criteria = new \stdClass();
        $criteria->customerId = $customerId;

        $qDocumentType = CustomerModel::getEmployeeDocumentTypeRelationRaw($criteria);

        return DB::table('wg_customers')
            ->join(DB::raw("({$qDocumentType->toSql()}) as document_type"), function ($join) {
                $join->on('wg_customers.id', '=', 'document_type.customer_id')
                    ->whereNull('document_type.customer_id', 'or');
            })
            ->mergeBindings($qDocumentType)
            // ->join(DB::raw(CustomerModel::getEmployeeDocumentTypeRelation('document_type')), function ($join) {

            //     $join->on('wg_customers.id', '=', 'document_type.customer_id')
            //         ->whereNull('document_type.customer_id', 'or');
            // })
            ->select('document_type.*')
            ->where('wg_customers.id', $customerId)
            ->orderBy('document_type.item')
            ->get();
    }

    public function getWorkplaceList($customerId)
    {
        return DB::table('wg_customers')
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_config_workplace.customer_id');
            })
            ->select('wg_customer_config_workplace.*')
            ->where('wg_customer_config_workplace.customer_id', $customerId)
            ->where('wg_customer_config_workplace.status', '=', 'Activo')
            ->orderBy('wg_customer_config_workplace.name')
            ->get()
            ->toArray();
    }

    public function getMacroprocessList($criteria)
    {
        return DB::table('wg_customers')
            ->join('wg_customer_config_macro_process', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_config_macro_process.customer_id');
            })
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_macro_process.workplace_id');
            })
            ->select('wg_customer_config_macro_process.*')
            ->where('wg_customer_config_macro_process.customer_id', $criteria->customerId)
            ->where('wg_customer_config_macro_process.workplace_id', $criteria->workplaceId)
            ->where('wg_customer_config_macro_process.status', '=', 'Activo')
            ->orderBy('wg_customer_config_macro_process.name')
            ->get();
    }

    public function getProcessList($criteria)
    {
        return DB::table('wg_customers')
            ->join('wg_customer_config_process', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_config_process.customer_id');
            })
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_process.workplace_id');
            })
            ->join('wg_customer_config_macro_process', function ($join) {
                $join->on('wg_customer_config_macro_process.id', '=', 'wg_customer_config_process.macro_process_id');
            })
            ->select('wg_customer_config_process.*')
            ->where('wg_customer_config_process.customer_id', $criteria->customerId)
            ->where('wg_customer_config_process.workplace_id', $criteria->workplaceId)
            ->where('wg_customer_config_process.macro_process_id', $criteria->macroprocessId)
            ->where('wg_customer_config_process.status', '=', 'Activo')
            ->orderBy('wg_customer_config_process.name')
            ->get();
    }

    public function getJobList($customerId)
    {
        return DB::table('wg_customers')
            ->join('wg_customer_config_job_data', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_config_job_data.customer_id');
            })
            ->select('wg_customer_config_job_data.*')
            ->where('wg_customer_config_job_data.customer_id', $customerId)
            ->where('wg_customer_config_job_data.status', '=', 'Activo')
            ->orderBy('wg_customer_config_job_data.name')
            ->get();
    }

    public function getActivityList($customerId)
    {
        return DB::table('wg_customers')
            ->join('wg_customer_config_activity', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_config_activity.customer_id');
            })
            ->select('wg_customer_config_activity.*')
            ->where('wg_customer_config_activity.customer_id', $customerId)
            ->where('wg_customer_config_activity.status', '=', 'Activo')
            ->orderBy('wg_customer_config_activity.name')
            ->get();
    }

    public function getHasEconomicGroupList($criteria)
    {
        $query = DB::table('wg_customers')
            ->join('wg_customer_economic_group', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_economic_group.parent_id');
            })
            ->join(DB::raw('wg_customers AS wg_economic_group'), function ($join) {
                $join->on('wg_economic_group.id', '=', 'wg_customer_economic_group.customer_id');
            })
            ->select(
                'wg_customers.id',
                'wg_customers.id AS value',
                'wg_customers.businessName AS item'
            )
            ->where('wg_customers.hasEconomicGroup', 1)
            ->where('wg_customers.status', 1)
            ->groupBy('wg_customers.id')
            ->orderBy('wg_customers.businessName');

        if (isset($criteria->customerId) && $criteria->customerId) {
            $query->where('wg_customers.id', $criteria->customerId);
        }

        return $query->get();
    }

    public function getEmployeerList($criteria)
    {
        $contractorClassification = SystemParameter::where('group', 'wg_customer_classification_dashboard')
            ->where('code', 'contractor')
            ->get()
            ->map(function($item) {
                return $item->value;
            })
            ->toArray();

        $economicGroupClassification = SystemParameter::where('group', 'wg_customer_classification_dashboard')
            ->where('code', 'economic_group')
            ->get()
            ->map(function($item) {
                return $item->value;
            })
            ->toArray();

        if ($criteria->isAdmin) {

            $allCustomersQuery = DB::table('wg_customers')
                ->whereIn('wg_customers.classification', $contractorClassification ? $contractorClassification : ['Contratante'])
                ->where('wg_customers.status', 1)
                ->groupBy('wg_customers.id')
                ->select(
                    'wg_customers.id',
                    'wg_customers.id AS value',
                    'wg_customers.businessName AS item'
                );

            $customerEconomicGroup = DB::table('wg_customers')
                ->whereIn('wg_customers.classification', $economicGroupClassification ? $economicGroupClassification : ['Empresa'])
                ->where('wg_customers.hasEconomicGroup', '1')
                ->where('wg_customers.status', 1)
                ->groupBy('wg_customers.id')
                ->select(
                    'wg_customers.id',
                    'wg_customers.id AS value',
                    'wg_customers.businessName AS item'
                );

            $customerWithout = DB::table('wg_customers')
                ->leftJoin('wg_customer_economic_group as eg', 'eg.customer_id', '=', 'wg_customers.id')
                ->whereIn('wg_customers.classification', $economicGroupClassification ? $economicGroupClassification : ['Empresa'])
                ->where('wg_customers.hasEconomicGroup', '0')
                ->where('wg_customers.status', 1)
                ->whereNull('eg.id')
                ->groupBy('wg_customers.id')
                ->select(
                    'wg_customers.id',
                    'wg_customers.id AS value',
                    'wg_customers.businessName AS item'
                );

            $query = $allCustomersQuery
                ->union($customerEconomicGroup)->mergeBindings($customerEconomicGroup)
                ->union($customerWithout)->mergeBindings($customerWithout);

            $result = DB::table(DB::raw("({$query->toSql()}) as t"))
                ->mergeBindings($query)
                ->groupBy('t.id')
                ->orderBy('t.item')
                ->get();


            return $result;


        } else if ($criteria->isCustomer) {

            $customerEconomicGroup = DB::table('wg_customers')
                ->join('wg_customer_economic_group as eg', 'wg_customers.id', '=', 'eg.parent_id')
                ->join('wg_customers AS customer_eg', 'customer_eg.id', '=', 'eg.customer_id')
                ->select(
                    'wg_customers.id',
                    'wg_customers.id AS value',
                    'wg_customers.businessName AS item',
                    'customer_eg.id as parentId'
                )
                ->where('wg_customers.hasEconomicGroup', '1')
                ->where('wg_customers.status', 1)
                ->where('customer_eg.status', 1)
                ->groupBy('wg_customers.id')
                ->orderBy('customer_eg.businessName');

            $customerContractors = DB::table('wg_customers')
                ->join('wg_customer_contractor', 'wg_customers.id', '=', 'wg_customer_contractor.customer_id')
                ->join('wg_customers AS wg_contractor', 'wg_contractor.id', '=', 'wg_customer_contractor.contractor_id')
                ->select(
                    'wg_customers.id',
                    'wg_customers.id AS value',
                    'wg_customers.businessName AS item',
                    'wg_contractor.id as parentId'
                )
                ->whereIn('wg_customers.classification', $contractorClassification ? $contractorClassification : ['Contratante'])
                ->where('wg_customers.status', 1)
                ->groupBy('wg_customers.id')
                ->orderBy('wg_customers.businessName');

            if (isset($criteria->customerId) && $criteria->customerId) {
                $customerContractors->where('wg_contractor.id', $criteria->customerId);
                $customerEconomicGroup
                    ->where('customer_eg.id', $criteria->customerId)
                    ->orWhere('eg.parent_id', $criteria->customerId);
            }

            $customerContractors->union($customerEconomicGroup)->mergeBindings($customerEconomicGroup);

            return $customerContractors->get();

        } else if ($criteria->isAgent) {
            $subquery = DB::table('wg_customer_agent')
                ->groupBy('customer_id', 'agent_id')
                ->select('customer_id', 'agent_id');

            return DB::table('wg_customers')
                ->join(DB::raw("({$subquery->toSql()}) as wg_customer_agent"), function($join) {
                    $join->on('wg_customers.id', 'wg_customer_agent.customer_id');
                })
                ->join('wg_agent', 'wg_agent.id', '=', 'wg_customer_agent.agent_id')
                ->join('users as users', 'users.id', '=', 'wg_agent.user_id')
                ->where('wg_customers.isDeleted', 0)
                ->where('users.id', $criteria->userId)
                ->select(
                    'wg_customers.id',
                    'wg_customers.id AS value',
                    'wg_customers.businessName AS item',
                    DB::raw("0 as parentId")
                )
                ->get();
        }

        return [];
    }

    public function getRelatedAgentAndUserList($customerId)
    {
        $q1 = DB::table('wg_agent')
            ->join('wg_customer_agent', function ($join) {
                $join->on('wg_agent.id', '=', 'wg_customer_agent.agent_id');
            })
            ->leftjoin('users', function ($join) {
                $join->on('users.id', '=', 'wg_agent.user_id');
            })
            ->select(
                'wg_agent.id',
                'wg_customer_agent.customer_id',
                'wg_agent.name',
                DB::raw("'Asesor' AS type"),
                DB::raw("users.email COLLATE utf8_general_ci AS email")
            )
            ->where('wg_customer_agent.customer_id', $customerId)
            ->groupBy(
                'wg_agent.id',
                'wg_customer_agent.customer_id'
            );

        $q2 = DB::table('wg_customer_user')
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'wg_customer_user.user_id');
            })
            ->select(
                'wg_customer_user.id',
                'wg_customer_user.customer_id',
                DB::raw("CONCAT_WS(' ', users.name, IFNULL(users.surname, '')) AS fullName"),
                DB::raw("'Cliente Usuario' AS type"),
                'users.email AS email'
            )
            ->where('wg_customer_user.isActive', 1)
            ->where('wg_customer_user.customer_id', $customerId)
            ->groupBy(
                'wg_customer_user.id',
                'wg_customer_user.customer_id'
            );

        $q1->union($q2)->mergeBindings($q2);

        return DB::table(DB::raw("({$q1->toSql()}) as responsible"))
            ->mergeBindings($q1)
            ->orderBy('responsible.name')
            ->get();
    }


    public function getSupportHelpInformation($customer, $user)
    {
        if ($customer == null)
        {
            return [];
        }

        $hasSpecialMatrix = $customer->hasSpecialMatrix();
        $matrixType = !$hasSpecialMatrix ? $customer->matrixType : "S";
        $classification = $customer->getClassification();

        $classificationCode = $classification ? $classification->code : null;
        $hasTotalEmployee = $customer->totalEmployee != null;
        $hasriskClass = $customer->riskClass != null;

        return [
            'hasClassification' => $classification != null,
            "isCustomer" => is_null($classificationCode) || $classificationCode != "NOCLIENT",
            "attentionLines" => $this->getAttentionLines($classification),
            "hasMatrix" => $matrixType != null,
            "hasBasicInformation" => $hasTotalEmployee && $hasriskClass,
            "hasMinimumStandard0312" => $customer->hasMinimumStandard0312(),
            "hasNotificationUser" => $customer->hasNotificationUser(),
            "email" => $this->getSupportEmail(),
            "isTermAndCondition" => $user->wg_term_condition == '1'
        ];
    }

    private function getAttentionLines($classification)
    {
        if ($classification && $classification->code == "NOCLIENT") {
            return $this->getParameterList("attention_lines_no_client");
        } elseif ($classification && (is_null($classification->code) || $classification->code != "NOCLIENT")) {
            return $this->getParameterList("attention_lines_client");
        }
    }

    private function getSupportEmail()
    {
        $support = $this->getParameterList('support_help_center', 'config');

        return (new Collection($support))->filter(function($param) {
            return $param->value == 'email';
        })->map(function($param) {
            return $param->item;
        })->first();
    }

    private function getParameterList($group, $namespace = 'wgroup')
    {
        return DB::table('system_parameters')
            ->select('item', 'value', 'code')
            ->where('namespace', $namespace)
            ->where('group', $group)
            ->orderBy("value")
            ->get();
    }


    public function getAmountEmployeesAll(int $customerId)
    {
        $countEmployees = DB::table('wg_customer_employee')->where('customer_id', $customerId)->count();
        $countEmployeesEconomicGroup = $this->getCountEmployeesEconomicGroup($customerId);
        $countEmployeesContrators = $this->getCountEmployeesContrators($customerId);
        $total = $countEmployees + $countEmployeesEconomicGroup + $countEmployeesContrators;

        $result = new \stdClass();
        $result->countEmployees = $countEmployees;
        $result->countEmployeesEconomicGroup = $countEmployeesEconomicGroup;
        $result->countEmployeesContrators = $countEmployeesContrators;
        $result->total = $total;

        return $result;
    }


    public function getCountEmployeesEconomicGroup(int $customerId)
    {
        $result = DB::table('wg_customer_economic_group as eg')
            ->leftJoin('wg_customer_employee as ce', 'ce.customer_id', '=', 'eg.customer_id')
            ->where('eg.parent_id', $customerId)
            ->select(DB::raw('count(DISTINCT ce.id) AS amount'))
            ->first();

        return $result->amount ?? 0;
    }


    public function getCountEmployeesContrators(int $customerId)
    {
        $result = DB::table('wg_customer_contractor as contractor')
            ->leftJoin('wg_customer_employee as ce', 'ce.customer_id', '=', 'contractor.contractor_id')
            ->where('contractor.customer_id', $customerId)
            ->select(DB::raw('count(DISTINCT ce.id) AS amount'))
            ->first();

        return $result->amount ?? 0;
    }

    public function getAmountEmployeesChartStackedBar(int $customerId)
    {
        $queryEmployee = DB::table('wg_customer_employee as ce')
            ->where('ce.customer_id', '=', $customerId)
            ->select(
                DB::raw("'Principal' as label"),
                DB::raw("'status' as stack"),
                DB::raw("COUNT(IF(ce.isActive = 1, ce.isActive, NULL)) AS count_actives"),
                DB::raw("COUNT(IF(ce.isActive = 0, ce.isActive, NULL)) AS count_inactives"),
                DB::raw("COUNT(IF(ce.isAuthorized  IS NULL OR ce.isAuthorized <> 1, 1, NULL)) AS count_not_autorized"),
                DB::raw("COUNT(IF(ce.isAuthorized = 1, 1, NULL)) AS count_autorized")
            );

        $queryEconomicGroup = DB::table('wg_customer_economic_group as eg')
            ->join('wg_customers as c', function($join) {
                $join->on('c.id', '=', 'eg.parent_id');
                $join->whereRaw('c.hasEconomicGroup = 1');
            })
            ->leftJoin('wg_customer_employee as ce', 'ce.customer_id', '=', 'eg.customer_id')
            ->where('eg.parent_id', $customerId)
            ->select(
                DB::raw("'Grupo' as label"),
                DB::raw("'authorized' as stack"),
                DB::raw("COUNT(IF(ce.isActive = 1, ce.isActive, NULL)) AS count_actives"),
                DB::raw("COUNT(IF(ce.isActive = 0, ce.isActive, NULL)) AS count_inactives"),
                DB::raw("COUNT(IF(ce.isAuthorized  IS NULL OR ce.isAuthorized <> 1, 1, NULL)) AS count_not_autorized"),
                DB::raw("COUNT(IF(ce.isAuthorized = 1, 1, NULL)) AS count_autorized")
            );

        $queryContrator = DB::table('wg_customer_contractor as contractor')
            ->leftJoin('wg_customer_employee as ce', 'ce.customer_id', '=', 'contractor.contractor_id')
            ->where('contractor.customer_id', $customerId)
            ->select(
                DB::raw("'Contratista' as label"),
                DB::raw("'Contratista2' as stack"),
                DB::raw("COUNT( DISTINCT IF(ce.isActive = 1, ce.id, NULL)) AS count_actives"),
                DB::raw("COUNT( DISTINCT IF(ce.isActive = 0, ce.id, NULL)) AS count_inactives"),
                DB::raw("COUNT( DISTINCT IF(ce.isAuthorized  IS NULL OR ce.isAuthorized <> 1, ce.id, NULL)) AS count_not_autorized"),
                DB::raw("COUNT( DISTINCT IF(ce.isAuthorized = 1, ce.id, NULL)) AS count_autorized")
            );

        $data = $queryEmployee
            ->union($queryEconomicGroup)->mergeBindings($queryEconomicGroup)
            ->union($queryContrator)->mergeBindings($queryContrator)
            ->get();

        if (empty($data)) {
            return [];
        }

        $labels = $data->pluck('label')->unique()->toArray();
        $stacks = $data->pluck('stack')->unique()->toArray();

        $config = array(
            "labelColumn" => $labels,
            "valueColumns" => [
                ['label' => 'Activos', 'field' => 'count_actives', 'color' => '#dfd500', 'stack' => 'status'],
                ['label' => 'Inactivos', 'field' => 'count_inactives', 'color' => '#22b14c', 'stack' => 'status'],
                ['label' => 'Autorizados', 'field' => 'count_autorized', 'color' => '#ffba4a', 'stack' => 'authorized'],
                ['label' => 'No Autorizados', 'field' => 'count_not_autorized', 'color' => '#1dabda', 'stack' => 'authorized'],
            ]
        );

        return $this->chart->getChartBarGroupedStack2($data, $config, $stacks);
    }

}
