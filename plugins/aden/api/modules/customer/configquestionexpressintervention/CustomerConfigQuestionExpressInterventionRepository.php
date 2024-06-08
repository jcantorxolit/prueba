<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigQuestionExpressIntervention;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Classes\ExcelSheet;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\ConfigQuestionExpress\CustomerConfigQuestionExpressRepository;
use AdeN\Api\Modules\Customer\CustomerModel;
use DB;
use Mail;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerConfigQuestionExpressInterventionRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigQuestionExpressInterventionModel());

        $this->service = new CustomerConfigQuestionExpressInterventionService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_question_express_intervention.id",
            "customerId" => "wg_customer_config_question_express_intervention.customer_id",
            "customerQuestionExpressId" => "wg_customer_config_question_express_intervention.customer_question_express_id",
            "name" => "wg_customer_config_question_express_intervention.name",
            "description" => "wg_customer_config_question_express_intervention.description",
            "responsibleType" => "wg_customer_config_question_express_intervention.responsible_type",
            "responsibleId" => "wg_customer_config_question_express_intervention.responsible_id",
            "amount" => "wg_customer_config_question_express_intervention.amount",
            "executionDate" => "wg_customer_config_question_express_intervention.execution_date",
            "isClosed" => "wg_customer_config_question_express_intervention.is_closed",
            "isHistorical" => "wg_customer_config_question_express_intervention.is_historical",
            "closedAt" => "wg_customer_config_question_express_intervention.closed_at",
            "closedBy" => "wg_customer_config_question_express_intervention.closed_by",
            "createdAt" => "wg_customer_config_question_express_intervention.created_at",
            "updatedAt" => "wg_customer_config_question_express_intervention.updated_at",
            "createdBy" => "wg_customer_config_question_express_intervention.created_by",
            "updatedBy" => "wg_customer_config_question_express_intervention.updated_by",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_config_question_express_intervention.parent_id', '=', 'tableParent.id');
		}
		*/


        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allResponsible($criteria)
    {
        $this->setColumns([
            "responsible" => "wg_customer_config_question_express_intervention.responsibleName",
            "responsibleEmail" => "wg_customer_config_question_express_intervention.responsibleEmail",
            "qty" => 'wg_customer_config_question_express_intervention.qty',
            "workplaceId" => "wg_customer_config_question_express_intervention.customer_workplace_id",
            "customerId" => "wg_customer_config_question_express_intervention.customer_id",
        ]);

        $this->parseCriteria($criteria);

        $qAgentUser = CustomerModel::getRelatedAgentAndUserRaw($criteria);

        $q1 = DB::table('wg_customer_config_question_express_intervention');

        $q1
            ->join('wg_customer_config_question_express', function ($join) {
                $join->on('wg_customer_config_question_express.id', '=', 'wg_customer_config_question_express_intervention.customer_question_express_id');
            })
            ->join(DB::raw("({$qAgentUser->toSql()}) as wg_custoemr_agent_user"), function ($join) {
                $join->on('wg_custoemr_agent_user.type', '=', 'wg_customer_config_question_express_intervention.responsible_type');
                $join->on('wg_custoemr_agent_user.id', '=', 'wg_customer_config_question_express_intervention.responsible_id');
            })
            ->select(
                'wg_custoemr_agent_user.name AS responsibleName',
                'wg_custoemr_agent_user.email AS responsibleEmail',
                DB::raw('COUNT(*) AS qty'),
                'wg_customer_config_question_express.customer_workplace_id',
                'wg_customer_config_question_express_intervention.customer_id'
            )
            ->mergeBindings($qAgentUser)
            ->where('wg_customer_config_question_express_intervention.is_historical', 0)
            ->where('wg_customer_config_question_express_intervention.is_closed', 0)
            ->groupBy(
                'wg_customer_config_question_express_intervention.customer_id',
                'wg_customer_config_question_express_intervention.responsible_type',
                'wg_customer_config_question_express_intervention.responsible_id'
            );

        $workplaceId = CriteriaHelper::getMandatoryFilter($criteria, 'workplaceId');

        if ($workplaceId) {
            $q1->groupBy('wg_customer_config_question_express.customer_workplace_id');
        }

        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) as wg_customer_config_question_express_intervention")))
            ->mergeBindings($q1);

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerId = $entity->customerId;
        $entityModel->customerQuestionExpressId = $entity->customerQuestionExpressId;
        $entityModel->name = $entity->name;
        $entityModel->description = $entity->description;
        $entityModel->responsibleType = $entity->responsible ? $entity->responsible->type : null;
        $entityModel->responsibleId = $entity->responsible ? $entity->responsible->id : null;
        $entityModel->amount = $entity->amount;
        $entityModel->executionDate = $entity->executionDate ? Carbon::parse($entity->executionDate)->timezone('America/Bogota') : null;
        $entityModel->isClosed = $entity->status;

        if ($entity->isClosed && $entityModel->closedAt == null) {
            $entityModel->closedAt = Carbon::now('America/Bogota');
            $entityModel->closedBy = $authUser ? $authUser->id : 1;
        }

        if ($isNewRecord) {
            $entityModel->isDeleted = false;
            $entityModel->isHistorical = false;
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();

            $this->onSendMail($entity);

        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();

            if ($entityModel->isDirty('responsible_id')) {
                $this->onSendMail($entity);
            }
        }

        if ($this->service->canUpdateQuestionStatus($entityModel->customerQuestionExpressId)) {
            (new CustomerConfigQuestionExpressRepository)->updateRateById($entity->customerQuestionExpressId, 'S');
            $query = $this->query();
            $query
                ->where('is_historical', 0)
                ->where('is_closed', 1)
                ->where('customer_question_express_id', $entityModel->customerQuestionExpressId)
                ->update([
                    'is_historical' => 1,
                    'updated_by' => $authUser ? $authUser->id : 1,
                    'updated_at' => DB::raw('NOW()')
                ]);
        }

        return $this->parseModelWithRelations($entityModel);
    }

    public function updateFiles($model, $fileInfo)
    {
        $newFile = new \stdClass();
        $newFile->default = true;
        $newFile->url = $fileInfo['path'];
        $newFile->name = $fileInfo['file'];
        $newFile->date = Carbon::now("America/Bogota");
        $newFile->id = $fileInfo['id'];

        $filesInfo = null;

        if ($model->filesInfo != "") {
            $filesInfo = ($files = json_decode($model->filesInfo)) ? $files : [];
        }

        $filesInfo[] = $newFile;

        $model->filesInfo = json_encode($filesInfo);
        $model->filesName = implode(',', array_map(function ($item) {
            return $item->name;
        }, $filesInfo));
        $model->save();
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $authUser = $this->getAuthUser();
        $entityModel->isDeleted = true;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        return $entityModel->save();
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->customerQuestionExpressId = $model->customerQuestionExpressId;
            $entity->name = $model->name;
            $entity->description = $model->description;
            $entity->responsible = CustomerModel::findAgentAndUserRaw($model->customerId, $model->responsibleId, $model->responsibleType);
            $entity->amount = $model->amount;
            $entity->executionDate = $model->executionDate ? Carbon::parse($model->executionDate) : null;
            $entity->isClosed = $model->isClosed == 1;
            $entity->status = $model->isClosed == 1;
            $entity->files = $this->service->getFiles($model->filesInfo);

            return $entity;
        } else {
            return null;
        }
    }

    public function getList($criteria)
    {
        return $this->service->getList($criteria);
    }

    public function exportExcel($criteria)
    {
        $data = $this->service->getExportExcelData($criteria);
        $filename = 'TABLERO_DE_PELIGROS_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'PLANES DE INTERVENCIÓN', $data);
    }

    public function exportGeneralExcel($criteria)
    {
        $generalData = $this->service->getExportExcelGeneralData($criteria);
        $interventionData = $this->service->getExportExcelGeneralInterventionData($criteria);
        $responsibleData = $this->service->getExportExcelGeneralResponsibleData($criteria);

        $excelSheet = (new ExcelSheet)
            ->addSheet('Nivel de riesgo total', $generalData)
            ->addSheet('Plan de Intervención', $interventionData)
            ->addSheet('Responsables', $responsibleData);

        $filename = 'TABLERO_GENERAL_' . Carbon::now()->timestamp;

        ExportHelper::excelMultipleSheets($filename, $excelSheet);
    }

    public function getYearList($criteria)
    {
        return $this->service->getYearList($criteria);
    }

    private function onSendMail($data)
    {
        try {        
            if ($data && $data->responsible && $data->responsible->email != "") {

                $templateName = "rainlab.user::mail.notificacion_plan_intervencion_matriz_ligera";

                $params['name'] = $data->name;
                $params['description'] = $data->description;
                $params['amount'] = $data->amount;
                $params['executionDate'] = $data->executionDate ? Carbon::parse($data->executionDate)->format('d/m/Y') : null;
                $params['responsible'] = $data->responsible;

                if ($templateName != "") {
                    Mail::send($templateName, $params, function ($message) use ($data) {                        
                        $message->to($data->responsible->email, $data->responsible->name);
                    });
                }
            }
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }
}
