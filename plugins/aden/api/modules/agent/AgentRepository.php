<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Agent;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

class AgentRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new AgentModel());

        $this->service = new AgentService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_agent.id",
            "documentType" => "tipodoc.item as document_type",
            "documentNumber" => "wg_agent.documentNumber",
            "type" =>  DB::raw("'Asesor' as type"),
            "fullName" => DB::raw("(CONCAT_WS(' ', wg_agent.firstName, wg_agent.lastName)) as fullName"),
            "firstName" => "wg_agent.firstName",
            "lastName" => "wg_agent.lastName",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation */
        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc')), function ($join) {
            $join->on('wg_agent.documentType', '=', 'tipodoc.value');

        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allAvailable($criteria)
    {
        $this->setColumns([
            "id" => "wg_agent.id",
            "documentType" => "tipodoc.item as document_type",
            "documentNumber" => "wg_agent.documentNumber",
            "type" =>  DB::raw("'Asesor' as type"),
            "fullName" => DB::raw("(CONCAT_WS(' ', wg_agent.firstName, wg_agent.lastName)) as fullName"),
            "firstName" => "wg_agent.firstName",
            "lastName" => "wg_agent.lastName",
        ]);

        $this->parseCriteria($criteria);

        $q1 = DB::table('wg_professor_event_detail_actor')
        ->select(
            'id', 'professor_event_detail_id', 'type', 'actor'
        )
        ->where('wg_professor_event_detail_actor.classification', 'IN') ;

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'professorEventDetailId') {
                        $q1->where('wg_professor_event_detail_actor.professor_event_detail_id', SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }

                    if ($item->field == 'type') {
                        $q1->where('wg_professor_event_detail_actor.type', SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = $this->query()->mergeBindings($q1);

        /* Example relation */
        $query->leftjoin(DB::raw("({$q1->ToSql()}) AS wg_professor_event_detail_actor"), function ($join) {
            $join->on('wg_agent.id', '=', 'wg_professor_event_detail_actor.actor');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc')), function ($join) {
            $join->on('wg_agent.documentType', '=', 'tipodoc.value');

        })
        ->whereNull('wg_professor_event_detail_actor.id');

        $this->applyCriteria($query, $criteria, ['professorEventDetailId', 'type']);

        return $this->get($query, $criteria);
    }

    public function allAvailableBatch($criteria)
    {
        $this->setColumns([
            "id" => "wg_agent.id",
            "documentType" => "tipodoc.item as document_type",
            "documentNumber" => "wg_agent.documentNumber",
            "type" =>  DB::raw("'Asesor' as type"),
            "fullName" => DB::raw("(CONCAT_WS(' ', wg_agent.firstName, wg_agent.lastName)) as fullName"),
            "firstName" => "wg_agent.firstName",
            "lastName" => "wg_agent.lastName",
        ]);

        $this->parseCriteria($criteria);


        $query = $this->query();

        /* Example relation */
        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc')), function ($join) {
            $join->on('wg_agent.documentType', '=', 'tipodoc.value');

        });

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

        $entityModel->legaltype = $entity->legaltype ? $entity->legaltype->value : null;
        $entityModel->name = $entity->name;
        $entityModel->firstname = $entity->firstname;
        $entityModel->lastname = $entity->lastname;
        $entityModel->documenttype = $entity->documenttype;
        $entityModel->documentnumber = $entity->documentnumber;
        $entityModel->gender = $entity->gender;
        $entityModel->type = $entity->type;
        $entityModel->active = $entity->active == 1;
        $entityModel->availability = $entity->availability;
        $entityModel->rh = $entity->rh;
        $entityModel->emergencycontactname = $entity->emergencycontactname;
        $entityModel->emergencycontactphone = $entity->emergencycontactphone ? $entity->emergencycontactphone->value : null;
        $entityModel->emergencycontactkinship = $entity->emergencycontactkinship;
        $entityModel->signaturetext = $entity->signaturetext;
        $entityModel->userId = $entity->userId ? $entity->userId->id : null;


        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        $result = $entityModel;

        return $result;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->name = $model->name;
            $entity->firstName = $model->firstname;
            $entity->lastName = $model->lastname;
            $entity->fullName = $model->firstname . " " . $model->lastname;
            $entity->documentType = $model->getDocumentType();
            $entity->documentNumber = $model->documentNumber;
            $entity->type = $model->getType();


            return $entity;
        } else {
            return null;
        }
    }
}
