<?php	
/**
 * User: DAB
 * Date: 25/09/2018
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Project\AgentTracking\Http\Controllers;

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

use AdeN\Api\Modules\Project\AgentTracking\CustomerProjectAgentTrackingRepository;

class CustomerProjectAgentTrackingController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
		parent::__construct();
		
        $this->repository = new CustomerProjectAgentTrackingRepository();
		$this->request = app('Input');
		
        $this->run();
    }

    public function index()
    {
		$request = Request::instance();
		
        $content = $request->getContent();

        try {

            $mandatoryFilters = [
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
		
			$defaultFilters = [
array( "field" => 'id', "operator" => 'like', "value" => $criteria->search ),
array( "field" => 'projectAgentId', "operator" => 'like', "value" => $criteria->search ),
array( "field" => 'type', "operator" => 'like', "value" => $criteria->search ),
array( "field" => 'observation', "operator" => 'like', "value" => $criteria->search ),
array( "field" => 'estimatedhours', "operator" => 'like', "value" => $criteria->search ),
array( "field" => 'assignedhours', "operator" => 'like', "value" => $criteria->search ),
array( "field" => 'scheduledhours', "operator" => 'like', "value" => $criteria->search ),
array( "field" => 'runninghours', "operator" => 'like', "value" => $criteria->search ),
array( "field" => 'createdby', "operator" => 'like', "value" => $criteria->search ),
array( "field" => 'updatedby', "operator" => 'like', "value" => $criteria->search ),
array( "field" => 'createdAt', "operator" => 'like', "value" => $criteria->search ),
array( "field" => 'updatedAt', "operator" => 'like', "value" => $criteria->search ),
];

$criteria = CriteriaHelper::addFilters( $criteria, $defaultFilters );


            $result = $this->repository->all($criteria);

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

            //return Response::download($file, $model->document->file_name, $headers);
            return $model->document->download();

        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
            return Response::json($this->response, $this->response->getStatuscode());
        }
    }
}