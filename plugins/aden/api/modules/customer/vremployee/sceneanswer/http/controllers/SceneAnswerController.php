<?php	
/**
 * User: DAB
 * Date: 25/09/2018
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\VrEmployee\SceneAnswer\Http\Controllers;

use DB;
use Excel;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Request;
use Response;
use Session;
use Validator;

use Wgroup\Traits\UserSecurity;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;

use AdeN\Api\Modules\Customer\VrEmployee\SceneAnswer\SceneAnswerRepository;

class SceneAnswerController extends BaseController
{
    use UserSecurity;
    private $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new SceneAnswerRepository();
        $this->request = app('Input');
        $this->run();
    }

    public function getSummary()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerVrEmployeeId', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'experience', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'scene', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'question', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'answer', "operator" => 'like', "value" => $criteria->search)
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->summary($criteria);

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

    public function getAllSummary()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerEmployeeId', "operator" => 'eq'),
                array("field" => 'selectedYear', "operator" => 'eq'),
                array("field" => 'customerId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'date', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'experience', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'scene', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'question', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'answer', "operator" => 'like', "value" => $criteria->search)
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->allSummary($criteria);

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


    public function getExperienceByEmployee($experience)
    {
        $request = Request::instance();
        $content = $request->getContent();

        if ($experience == '0') {
            $this->response->setData([]);
            return Response::json($this->response, $this->response->getStatuscode());
        }

        try {
            $mandatoryFilters = [
                array("field" => 'customerEmployeeId', "operator" => 'eq'),
                array("field" => 'year', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'experienceCode', "operator" => 'eq', "value" => $experience),
            ]);

            $result = $this->repository->getExperienceByEmployee($criteria);

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

}