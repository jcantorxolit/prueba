<?php

namespace Wgroup\CustomerMatrixData;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\CustomerMatrixActivity\CustomerMatrixActivity;
use Wgroup\CustomerMatrixEnvironmentalAspect\CustomerMatrixEnvironmentalAspect;
use Wgroup\CustomerMatrixEnvironmentalAspect\CustomerMatrixEnvironmentalAspectDTO;
use Wgroup\CustomerMatrixProject\CustomerMatrixProject;


class CustomerMatrixDataService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerConfigWorkPlaceRepository;

    function __construct()
    {
        // $this->customerRepository = new CustomerRepository();
    }

    public function init()
    {
        parent::init();
    }

    /**
     * @param $search
     * @param int $perPage
     * @param int $currentPage
     * @param array $sorting
     * @param string $typeFilter
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerMatrixId)
    {

        $model = new CustomerMatrixData();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerConfigWorkPlaceRepository = new CustomerMatrixDataRepository($model);

        if ($perPage > 0) {
            $this->customerConfigWorkPlaceRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_matrix_data.id'
        ];

        $i = 0;

        foreach ($sorting as $key => $value) {
            try {

                if (isset($value["column"]) === false) {
                    continue;
                }

                $col = $value["column"];
                $dir = $value["dir"];

                $colName = $columns[$col];

                if ($colName == "") {
                    continue;
                }

                if ($dir == null || $dir == "") {
                    $dir = " asc ";
                }

                if ($i == 0) {
                    $this->customerConfigWorkPlaceRepository->sortBy($colName, $dir);
                } else {
                    $this->customerConfigWorkPlaceRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerConfigWorkPlaceRepository->sortBy('wg_customer_matrix_data.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_matrix_data.customer_matrix_id', $customerMatrixId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_matrix_data.name', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_matrix_data.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerMatrixId)
    {

        $model = new CustomerMatrixData();
        $this->customerConfigWorkPlaceRepository = new CustomerMatrixDataRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_matrix_data.customer_matrix_id', $customerMatrixId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_matrix_data.name', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_matrix_data.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, true, "");
    }

    public function getProjectList($customerMatrixId)
    {
        return CustomerMatrixProject::whereCustomerMatrixId($customerMatrixId)->get();
    }

    public function getActivityList($customerMatrixId)
    {
        return CustomerMatrixActivity::whereCustomerMatrixId($customerMatrixId)->get();
    }

    public function getAspectList($customerMatrixId)
    {
        return CustomerMatrixEnvironmentalAspectDTO::parse(CustomerMatrixEnvironmentalAspect::whereCustomerMatrixId($customerMatrixId)->get());
    }

    public function findAll($search, $perPage = 10, $currentPage = 0, $customerMatrixId = 0, $filter = null)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM
(
SELECT cmd.id,
       cmp.`name` `project`,
       cma.`name` `activity`,
       cmea.`name` `aspect`,
       cmei.`name` `impact`,
       cmd.`environmental_impact_in` `environmentalImpactIn`,
       cmd.`environmental_impact_ex` `environmentalImpactEx`,
       cmd.`environmental_impact_pr` `environmentalImpactPr`,
       cmd.`environmental_impact_re` `environmentalImpactRe`,
       cmd.`environmental_impact_rv` `environmentalImpactRv`,
       cmd.`environmental_impact_se` `environmentalImpactSe`,
       cmd.`environmental_impact_fr` `environmentalImpactFr`,
       (3 * IFNULL(cmd.`environmental_impact_in`,0)) + (2 * IFNULL(cmd.`environmental_impact_ex`,0)) + IFNULL(cmd.`environmental_impact_pr`,0) + IFNULL(cmd.`environmental_impact_re`,0) + IFNULL(cmd.`environmental_impact_rv`,0) + IFNULL(cmd.`environmental_impact_se`,0) + IFNULL(cmd.`environmental_impact_fr`,0) nia,
       cmd.`legal_impact_e` `legalImpactE`,
       cmd.`legal_impact_c` `legalImpactC`,
       IFNULL(cmd.`legal_impact_e`,0) + IFNULL(cmd.`legal_impact_c`,0) legalImpactCriterion,
       cmd.`interested_part_ac` `interestedPartAc`,
       cmd.`interested_part_ge` `interestedPartGe`,
       IFNULL(cmd.`interested_part_ac`,0) + IFNULL(cmd.`interested_part_ge`,0) interestedPartCriterion,
       (3 * IFNULL(cmd.`environmental_impact_in`,0)) + (2 * IFNULL(cmd.`environmental_impact_ex`,0)) + IFNULL(cmd.`environmental_impact_pr`,0) + IFNULL(cmd.`environmental_impact_re`,0) + IFNULL(cmd.`environmental_impact_rv`,0) + IFNULL(cmd.`environmental_impact_se`,0) + IFNULL(cmd.`environmental_impact_fr`,0) + IFNULL(cmd.`legal_impact_e`,0) + IFNULL(cmd.`legal_impact_c`,0) + IFNULL(cmd.`interested_part_ac`,0) + IFNULL(cmd.`interested_part_ge`,0) totalAspect,
       nature.`item` nature,
       cmd.`emergency_condition_in` `emergencyConditionIn`,
       cmd.`emergency_condition_ex` `emergencyConditionEx`,
       cmd.`emergency_condition_pr` `emergencyConditionPr`,
       cmd.`emergency_condition_re` `emergencyConditionRe`,
       cmd.`emergency_condition_rv` `emergencyConditionRv`,
       cmd.`emergency_condition_se` `emergencyConditionSe`,
       cmd.`emergency_condition_fr` `emergencyConditionFr`,
       (3 * IFNULL(cmd.`emergency_condition_in`,0)) + (2 * IFNULL(cmd.`emergency_condition_ex`,0)) + IFNULL(cmd.`emergency_condition_pr`,0) + IFNULL(cmd.`emergency_condition_re`,0) + IFNULL(cmd.`emergency_condition_rv`,0) + IFNULL(cmd.`emergency_condition_se`,0) + IFNULL(cmd.`emergency_condition_fr`,0) emergencyNia,
       MAX(CASE
               WHEN cmdc.type = '001' THEN cmdc.description
           END) `controlTypeE`,
       MAX(CASE
               WHEN cmdc.type = '002' THEN cmdc.description
           END) `controlTypeS`,
       MAX(CASE
               WHEN cmdc.type = '003' THEN cmdc.description
           END) `controlTypeCI`,
       MAX(CASE
               WHEN cmdc.type = '004' THEN cmdc.description
           END) `controlTypeCA`,
       MAX(CASE
               WHEN cmdc.type = '005' THEN cmdc.description
           END) `controlTypeSL`,
       MAX(CASE
               WHEN cmdc.type = '006' THEN cmdc.description
           END) `controlTypeEPP`,
       cmd.`associate_program` `associateProgram`,
       cmd.`registry`,
       cmdr.responsible
FROM `wg_customer_matrix_data` cmd
INNER JOIN `wg_customer_matrix` ON `wg_customer_matrix`.`id` = `cmd`.`customer_matrix_id`
INNER JOIN `wg_customer_matrix_project` cmp ON cmp.id = cmd.customer_matrix_project_id
INNER JOIN `wg_customer_matrix_activity` cma ON cma.id = cmd.customer_matrix_activity_id
LEFT JOIN `wg_customer_matrix_environmental_aspect` cmea ON cmea.id = cmd.customer_matrix_environmental_aspect_id
LEFT JOIN `wg_customer_matrix_environmental_impact` cmei ON cmei.id = cmd.customer_matrix_environmental_impact_id
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_in') iain ON cmd.environmental_impact_in = iain.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_ex') iaex ON cmd.environmental_impact_ex = iaex.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_pr') iapr ON cmd.environmental_impact_pr = iapr.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_re') iare ON cmd.environmental_impact_re = iare.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_rv') iarv ON cmd.environmental_impact_rv = iarv.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_se') iase ON cmd.environmental_impact_se = iase.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_fr') iafr ON cmd.environmental_impact_fr = iafr.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_legal_impact_e') lie ON cmd.legal_impact_e = lie.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_legal_impact_e') lic ON cmd.legal_impact_c = lic.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_interested_part_ac') ipac ON cmd.interested_part_ac = ipac.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_interested_part_ge') ipge ON cmd.interested_part_ge = ipge.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_nature') nature ON cmd.nature = nature.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_in') ecin ON cmd.emergency_condition_in = ecin.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_ex') ecex ON cmd.emergency_condition_ex = ecex.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_pr') ecpr ON cmd.emergency_condition_pr = ecpr.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_re') ecre ON cmd.emergency_condition_re = ecre.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_rv') ecrv ON cmd.emergency_condition_rv = ecrv.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_se') ecse ON cmd.emergency_condition_se = ecse.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_fr') ecfr ON cmd.emergency_condition_fr = ecfr.`value`
LEFT JOIN `wg_customer_matrix_data_control` cmdc ON `cmdc`.`customer_matrix_data_id` = `cmd`.`id`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_control_type') ct ON cmdc.type = ct.`value`
LEFT JOIN
  ( SELECT customer_matrix_data_id,
           GROUP_CONCAT(r.item) responsible
   FROM `wg_customer_matrix_data_responsible` cmdr
   LEFT JOIN
     (SELECT `item`,
             `value` COLLATE utf8_general_ci AS `value`
      FROM `system_parameters`
      WHERE `namespace` = 'wgroup'
        AND `group` = 'matrix_responsible') r ON cmdr.responsible = r.`value`
   GROUP BY customer_matrix_data_id) cmdr ON cmdc.customer_matrix_data_id = `cmd`.`id`
WHERE `cmd`.`customer_matrix_id` = :customer_matrix_id
GROUP BY `cmd`.id
) p
";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.id DESC ";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            /*$where = " WHERE (p.documentNumber like '%$search%' or p.firstName like '%$search%' or p.lastName like '%$search%' or p.neighborhood like '%$search%'
                            or p.workPlace like '%$search%' or p.job like '%$search%' or p.isActive like '%$search%' or p.isAuthorized like '%$search%')";*/
        }

        /*if ($where == "") {
            $where = ' WHERE p.customer_id = :customer_id';
        } else {
            $where .= ' AND p.customer_id = :customer_id';
        }*/

        $sql = $query . $where . $orderBy;
        $sql .= $limit;

        $results = DB::select($sql, array(
            'customer_matrix_id' => $customerMatrixId
        ));

        return $results;
    }

    public function findAllCount($customerMatrixId = 0, $filter = null)
    {
        $query = "SELECT * FROM
(
SELECT cmd.id,
       cmp.`name` `project`,
       cma.`name` `activity`,
       cmea.`name` `aspect`,
       cmei.`name` `impact`,
       cmd.`environmental_impact_in` `environmentalImpactIn`,
       cmd.`environmental_impact_ex` `environmentalImpactEx`,
       cmd.`environmental_impact_pr` `environmentalImpactPr`,
       cmd.`environmental_impact_re` `environmentalImpactRe`,
       cmd.`environmental_impact_rv` `environmentalImpactRv`,
       cmd.`environmental_impact_se` `environmentalImpactSe`,
       cmd.`environmental_impact_fr` `environmentalImpactFr`,
       (3 * IFNULL(cmd.`environmental_impact_in`,0)) + (2 * IFNULL(cmd.`environmental_impact_ex`,0)) + IFNULL(cmd.`environmental_impact_pr`,0) + IFNULL(cmd.`environmental_impact_re`,0) + IFNULL(cmd.`environmental_impact_rv`,0) + IFNULL(cmd.`environmental_impact_se`,0) + IFNULL(cmd.`environmental_impact_fr`,0) nia,
       cmd.`legal_impact_e` `legalImpactE`,
       cmd.`legal_impact_c` `legalImpactC`,
       IFNULL(cmd.`legal_impact_e`,0) + IFNULL(cmd.`legal_impact_c`,0) legalImpactCriterion,
       cmd.`interested_part_ac` `interestedPartAc`,
       cmd.`interested_part_ge` `interestedPartGe`,
       IFNULL(cmd.`interested_part_ac`,0) + IFNULL(cmd.`interested_part_ge`,0) interestedPartCriterion,
       IFNULL(cmd.`legal_impact_e`,0) + IFNULL(cmd.`legal_impact_c`,0) + IFNULL(cmd.`interested_part_ac`,0) + IFNULL(cmd.`interested_part_ge`,0) totalAspect,
       nature.`item` nature,
       cmd.`emergency_condition_in` `emergencyConditionIn`,
       cmd.`emergency_condition_ex` `emergencyConditionEx`,
       cmd.`emergency_condition_pr` `emergencyConditionPr`,
       cmd.`emergency_condition_re` `emergencyConditionRe`,
       cmd.`emergency_condition_rv` `emergencyConditionRv`,
       cmd.`emergency_condition_se` `emergencyConditionSe`,
       cmd.`emergency_condition_fr` `emergencyConditionFr`,
       (3 * IFNULL(cmd.`emergency_condition_in`,0)) + (2 * IFNULL(cmd.`emergency_condition_ex`,0)) + IFNULL(cmd.`emergency_condition_pr`,0) + IFNULL(cmd.`emergency_condition_re`,0) + IFNULL(cmd.`emergency_condition_rv`,0) + IFNULL(cmd.`emergency_condition_se`,0) + IFNULL(cmd.`emergency_condition_fr`,0) emergencyNia,
       MAX(CASE
               WHEN cmdc.type = '001' THEN cmdc.description
           END) `controlTypeE`,
       MAX(CASE
               WHEN cmdc.type = '002' THEN cmdc.description
           END) `controlTypeS`,
       MAX(CASE
               WHEN cmdc.type = '003' THEN cmdc.description
           END) `controlTypeCI`,
       MAX(CASE
               WHEN cmdc.type = '004' THEN cmdc.description
           END) `controlTypeCA`,
       MAX(CASE
               WHEN cmdc.type = '005' THEN cmdc.description
           END) `controlTypeSL`,
       MAX(CASE
               WHEN cmdc.type = '006' THEN cmdc.description
           END) `controlTypeEPP`,
       cmd.`associate_program` `associateProgram`,
       cmd.`registry`,
       cmdr.responsible
FROM `wg_customer_matrix_data` cmd
INNER JOIN `wg_customer_matrix` ON `wg_customer_matrix`.`id` = `cmd`.`customer_matrix_id`
INNER JOIN `wg_customer_matrix_project` cmp ON cmp.id = cmd.customer_matrix_project_id
INNER JOIN `wg_customer_matrix_activity` cma ON cma.id = cmd.customer_matrix_activity_id
LEFT JOIN `wg_customer_matrix_environmental_aspect` cmea ON cmea.id = cmd.customer_matrix_environmental_aspect_id
LEFT JOIN `wg_customer_matrix_environmental_impact` cmei ON cmei.id = cmd.customer_matrix_environmental_impact_id
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_in') iain ON cmd.environmental_impact_in = iain.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_ex') iaex ON cmd.environmental_impact_ex = iaex.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_pr') iapr ON cmd.environmental_impact_pr = iapr.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_re') iare ON cmd.environmental_impact_re = iare.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_rv') iarv ON cmd.environmental_impact_rv = iarv.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_se') iase ON cmd.environmental_impact_se = iase.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_fr') iafr ON cmd.environmental_impact_fr = iafr.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_legal_impact_e') lie ON cmd.legal_impact_e = lie.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_legal_impact_e') lic ON cmd.legal_impact_c = lic.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_interested_part_ac') ipac ON cmd.interested_part_ac = ipac.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_interested_part_ge') ipge ON cmd.interested_part_ge = ipge.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_nature') nature ON cmd.nature = nature.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_in') ecin ON cmd.emergency_condition_in = ecin.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_ex') ecex ON cmd.emergency_condition_ex = ecex.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_pr') ecpr ON cmd.emergency_condition_pr = ecpr.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_re') ecre ON cmd.emergency_condition_re = ecre.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_rv') ecrv ON cmd.emergency_condition_rv = ecrv.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_se') ecse ON cmd.emergency_condition_se = ecse.`value`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_environmental_impact_fr') ecfr ON cmd.emergency_condition_fr = ecfr.`value`
LEFT JOIN `wg_customer_matrix_data_control` cmdc ON `cmdc`.`customer_matrix_data_id` = `cmd`.`id`
LEFT JOIN
  (SELECT `item`,
          `value` COLLATE utf8_general_ci AS `value`
   FROM `system_parameters`
   WHERE `namespace` = 'wgroup'
     AND `group` = 'matrix_control_type') ct ON cmdc.type = ct.`value`
LEFT JOIN
  ( SELECT customer_matrix_data_id,
           GROUP_CONCAT(r.item) responsible
   FROM `wg_customer_matrix_data_responsible` cmdr
   LEFT JOIN
     (SELECT `item`,
             `value` COLLATE utf8_general_ci AS `value`
      FROM `system_parameters`
      WHERE `namespace` = 'wgroup'
        AND `group` = 'matrix_responsible') r ON cmdr.responsible = r.`value`
   GROUP BY customer_matrix_data_id) cmdr ON cmdc.customer_matrix_data_id = `cmd`.`id`
WHERE `cmd`.`customer_matrix_id` = :customer_matrix_id
GROUP BY `cmd`.id
) p
";

        $orderBy = " ORDER BY p.id DESC ";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        }

        /*if ($where == "") {
            $where = ' WHERE p.customer_id = :customer_id';
        } else {
            $where .= ' AND p.customer_id = :customer_id';
        }*/

        $sql = $query . $where . $orderBy;

        $results = DB::select($sql, array(
            'customer_matrix_id' => $customerMatrixId
        ));

        return count($results);
    }

    private function getWhere($filters)
    {
        $where = "";
        $lastFilter = null;
        foreach ($filters as $filter) {

            if ($lastFilter == null) {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= "p." . $filter->field->name . " = '" . $filter->value . "' ";
                        break;

                    case "LIKE":
                        $where .= "p." . $filter->field->name . " LIKE '%" . $filter->value . "%' ";
                        break;

                    case "<>":
                        $where .= "p." . $filter->field->name . " <> '" . $filter->value . "' ";
                        break;

                    case "<":
                        $where .= "p." . $filter->field->name . " < '" . $filter->value . "' ";
                        break;

                    case ">":
                        $where .= "p." . $filter->field->name . " > '" . $filter->value . "' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            } else {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " = '" . $filter->value . "' ";
                        break;

                    case "LIKE":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " LIKE '%" . $filter->value . "%' ";
                        break;

                    case "<>":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " <> '" . $filter->value . "' ";
                        break;

                    case "<":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " < '" . $filter->value . "' ";
                        break;

                    case ">":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " > '" . $filter->value . "' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            }

        }

        return $where == "" ? "" : " WHERE " . $where;
    }
}
