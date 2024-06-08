<?php


namespace AdeN\Api\Modules\PositivaFgn\GestPos\Activity\Evidence;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

use function GuzzleHttp\Promise\all;

class EvidenceRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new EvidenceModel());
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_gestpos_evidence.id",
            "evidence" => "positiva_fgn_gestpos_evidence.item AS evidence",
            "isRequired" => DB::raw("IF(wg_positiva_fgn_gestpos_evidence.is_required=1,'SI','NO') AS isRequired"),
            "gestposId" => "wg_positiva_fgn_gestpos_evidence.gestpos_id as gestposId"
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query()
                ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_gestpos_evidence')), function ($join) {
                    $join->on('wg_positiva_fgn_gestpos_evidence.evidence', '=', 'positiva_fgn_gestpos_evidence.value');
                });
    
        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }


    public function insertOrUpdate($entity)
    {
        $authUser = $this->getAuthUser();
        $entityModel = $this->model->newInstance();

        $entityModel->id = null;
        $entityModel->gestposId = $entity->gestposId;
        $entityModel->evidence = $entity->evidence->value;
        $entityModel->isRequired = $entity->isRequired == 1;

        $entityModel->createdAt = Carbon::now();
        $entityModel->createdBy = $authUser ? $authUser->id : 1;
        $entityModel->save();

        $entity->id = $entityModel->id;
        return $entity;
    }

    public function parseModelWithRelations(EvidenceModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->gestposId = $model->gestposId;
            $entity->isRequired = $model->isRequired == 1;
            $entity->evidence = $model->getEvidence();

            return $entity;
        }
         else {
            return null;
        }
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();
    }


}
