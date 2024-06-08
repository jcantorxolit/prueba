<?php	


namespace AdeN\Api\Modules\Customer\VrEmployee\ExperienceObservation\Http\Controllers;

use DB;
use Excel;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Request;
use Response;

use Wgroup\Traits\UserSecurity;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;

use AdeN\Api\Modules\Customer\VrEmployee\ExperienceObservation\ExperienceObservationRepository;

class ExperienceObservationController extends BaseController
{
    use UserSecurity;
    private $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new ExperienceObservationRepository();
        $this->request = app('Input');
        $this->run();
    }

    public function store()
    {
        $content = $this->request->get("data", "");
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

}