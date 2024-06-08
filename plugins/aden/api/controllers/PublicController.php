<?php

namespace AdeN\Api\Controllers;

use Carbon\Carbon;
use DB;
use Exception;
use Log;
use Response;

use Wgroup\Models\Town;
use AdeN\Api\Modules\Customer\CustomerRepository;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\HttpHelper;
use RainLab\User\Models\Country;
use RainLab\User\Models\State;

class PublicController extends BaseController
{

    public function __construct()
    {
        $this->request = app('Input');
        parent::__construct();
    }

    public function index()
    {
        $input = $this->request->get("data", "");
        $base64 = $this->request->get("base64", "1");
        $base64 = $base64 == '1' ? true : false;

        try {
            $entities = HttpHelper::parse($input, $base64);
            if ($entities != null) {
                foreach ($entities as $entity) {
                    switch ($entity->name) {

                        case 'country':
                            $result['countryList'] = Country::isEnabled()->orderBy("name", "asc")->get();
                            break;

                        case 'state_full':
                            $result['stateList'] = State::orderBy('name', 'ASC')->get();
                            break;

                        case 'city_full':
                            $result['cityList'] = Town::orderBy('name', 'ASC')->get();
                            break;

                        case 'customer_employee_document_type':
                            $repository = new CustomerRepository();
                            $result['customerEmployeeDocumentType'] = $repository->getEmployeeDocumentTypeList($entity->value);
                            break;

                        default:
                            $result[$entity->name] = $this->getSystemParameter($entity->name);
                            break;
                    }
                }
            }

            $this->response->setData($result);
            $this->response->setRecordsTotal(0);
            $this->response->setRecordsFiltered(0);
        } 
        catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    private function getSystemParameter($group)
    {
        $repository = new SystemParameter();
        return $repository->whereNamespace("wgroup")->whereGroup($group)
            ->orderBy('id', 'ASC')
            ->get();
    }
}
