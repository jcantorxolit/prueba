<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 25/11/2017
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers;

use DB;
use Excel;
use Illuminate\Support\Facades\Input;
use Log;
use Request;
use Response;
use Session;
use Validator;
use Exception;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;
use Wgroup\Traits\UserSecurity;

use AdeN\Api\Modules\Customer\AbsenteeismIndicator\CustomerAbsenteeismIndicatorRepository;
use AdeN\Api\Helpers\KendoCriteriaHelper;

class CustomerAbsenteeismIndicatorController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerAbsenteeismIndicatorRepository();
        $this->request = app('Input');

        $this->run();
    }

    public function index()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'resolution', "operator" => 'eq', "value" => '1111')
            ]);

            $defaultFilters = [
                array("field" => 'classification', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'period', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'name', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'manHoursWorked', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'disabilityDays', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'eventNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'directCost', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'indirectCost', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'diseaseRate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'frequencyIndex', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'severityIndex', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'disablingInjuriesIndex', "operator" => 'like', "value" => $criteria->search)
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->all($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexSummary()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'id', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'label', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'value', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'goal', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allSummary($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexParent()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq')
            ];

            $criteria = KendoCriteriaHelper::parse($content, $mandatoryFilters);

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'resolution', "operator" => 'eq', "value" => '0312')
            ]);

            $result = $this->repository->allParentResolution0312($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexDetail()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'cause', "operator" => 'eq'),
                array("field" => 'period', "operator" => 'eq'),
            ];

            $criteria = KendoCriteriaHelper::parse($content, $mandatoryFilters);

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'resolution', "operator" => 'eq', "value" => '0312')
            ]);

            $result = $this->repository->allDetailResolution0312($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexFrequencyAccidentality()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'year', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'month', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'eventNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'employeeQuantity', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'result', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allFrequencyAccidentality($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexSeverityAccidentality()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'year', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'month', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'disabilityDays', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'chargedDays', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'employeeQuantity', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'result', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allSeverityAccidentality($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexMortalProportionAccidentality()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                //array("field" => 'year', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'month', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'eventMortalNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'eventNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'result', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allMortalProportionAccidentality($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexAbsenteeismMedicalCause()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'year', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'month', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'disabilityDays', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'programedDays', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'result', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allAbsenteeismMedicalCause($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexOccupationalDiseaseFatalityRate()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                //array("field" => 'year', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'month', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'disabilityDays', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'programedDays', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'result', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allOccupationalDiseaseFatalityRate($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexOccupationalDiseasePrevalence()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'month', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'disabilityDays', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'programedDays', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'result', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allOccupationalDiseasePrevalence($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexOccupationalDiseaseIncidence()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'month', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'disabilityDays', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'programedDays', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'result', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allOccupationalDiseaseIncidence($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function store()
    {
        $content = $this->request->get("data", "");;

        try {
            $entity = HttpHelper::parse($content, true);
            $result = $this->repository->insertOrUpdate($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function update()
    {
        $content = $this->request->get("data", "");;

        try {
            $entity = HttpHelper::parse($content, true);
            $result = $this->repository->batchUpdate($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function destroy()
    {
        $id = $this->request->get("id", "");

        try {
            $this->repository->delete($id);
            $this->response->setResult(1);
        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function show()
    {
        $id = $this->request->get("id", "");

        try {
            $result = $this->repository->parseModelWithRelations($this->repository->find($id));
            $this->response->setResult($result);
        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function upload()
    {
        $id = $this->request->get("id", "0");
        try {

            $allFiles = Input::file();

            $model = $this->repository->find($id);

            foreach ($allFiles as $file) {
                $this->repository->checkUploadPostBack($file, $model);
            }
            $model = $this->repository->find($id);
            $this->response->setResult($model);
        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function download()
    {
        $id = $this->request->get("id", "0");
        try {

            $model = $this->repository->find($id);

            $file = $model->document->getDiskPath();

            $headers = $this->repository->getDownloadHeaders($model->document);

            //return Response::download($file, $model->document->file_name, $headers);\n\t
            return $model->document->download();

        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
            return Response::json($this->response, $this->response->getStatuscode());
        }
    }

    public function consolidate()
    {
        $request = Request::instance();

        $id = $this->request->get("id", "");

        try {

            $segments = $request->segments();

            if (in_array('consolidate-1111', $segments)) {
                $result = $this->repository->consolidate($id, '1111');
            } else if (in_array('consolidate-0312', $segments)) {
                $result = $this->repository->consolidate($id, '0312');
            }

            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function export()
    {

        set_time_limit(0);
        $request = Request::instance();

        $content = $this->request->get("data", "");
        $customerId = $this->request->get("customerId", "");
        $year = $this->request->get("year", "0");

        try {

            $segments = $request->segments();

            $criteria = CriteriaHelper::parse(HttpHelper::parse($content, true));

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'customerId', "operator" => 'eq', "value" => $customerId),
                array("field" => 'year', "operator" => 'eq', "value" => $year)
            ]);

            if (in_array('export-frequency-accidentality', $segments)) {
                $this->repository->exportExcelFrequencyAccidentality($criteria);
            } else if (in_array('export-severity-accidentality', $segments)) {
                $this->repository->exportExcelSeverityAccidentality($criteria);
            } else if (in_array('export-mortal-proportion-accidentality', $segments)) {
                $this->repository->exportExcelMortalProportionAccidentality($criteria);
            } else if (in_array('export-absenteeism-medical-cause', $segments)) {
                $this->repository->exportExcelAbsenteeismMedicalCause($criteria);
            } else if (in_array('export-occupational-disease-fatality-rate', $segments)) {
                $this->repository->exportExcelOccupationalDiseaseFatalityRate($criteria);
            } else if (in_array('export-occupational-disease-prevalence', $segments)) {
                $this->repository->exportExcelOccupationalDiseasePrevalence($criteria);
            } else if (in_array('export-occupational-disease-incidence', $segments)) {
                $this->repository->exportExcelOccupationalDiseaseIncidence($criteria);
            }


        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }

    public function exportParent()
    {

        set_time_limit(0);
        $content = $this->request->get("data", "");
        $customerId = $this->request->get("customerId", "");
        $year = $this->request->get("year", "0");

        try {

            $criteria = CriteriaHelper::parse(HttpHelper::parse($content, true));

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'customerId', "operator" => 'eq', "value" => $customerId)
            ]);

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'resolution', "operator" => 'eq', "value" => '0312')
            ]);

            $this->repository->exportExcelParent($criteria);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }
}
