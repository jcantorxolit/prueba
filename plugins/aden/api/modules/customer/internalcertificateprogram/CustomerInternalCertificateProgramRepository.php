<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\InternalCertificateProgram;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

class CustomerInternalCertificateProgramRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerInternalCertificateProgramModel());

        $this->service = new CustomerInternalCertificateProgramService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_internal_certificate_program.id",            
            "name" => "wg_customer_internal_certificate_program.name",
            "category" => "certificate_program_category.item AS category",            
            "capacity" => "wg_customer_internal_certificate_program.capacity",
            "hourDuration" => "wg_customer_internal_certificate_program.hourDuration",
            "authorizationResolution" => "wg_customer_internal_certificate_program.authorizationResolution",            
            "status" => "estado.item AS status",
            "isActive" => "wg_customer_internal_certificate_program.isActive",            
            "customerId" => "wg_customer_internal_certificate_program.customer_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
		$query->leftjoin(DB::raw(SystemParameter::getRelationTable('certificate_program_category')), function ($join) {
            $join->on('certificate_program_category.value', '=', 'wg_customer_internal_certificate_program.category');
            
		})->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('estado.value', '=', 'wg_customer_internal_certificate_program.isActive');
            
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

        $entityModel->customerId = $entity->customerId;
        $entityModel->code = $entity->code;
        $entityModel->name = $entity->name;
        $entityModel->amount = $entity->amount;
        $entityModel->currency = $entity->currency ? $entity->currency->value : null;
        $entityModel->category = $entity->category ? $entity->category->value : null;
        $entityModel->speciality = $entity->speciality ? $entity->speciality->value : null;
        $entityModel->capacity = $entity->capacity;
        $entityModel->hourduration = $entity->hourduration;
        $entityModel->validitynumber = $entity->validitynumber;
        $entityModel->validitytype = $entity->validitytype ? $entity->validitytype->value : null;
        $entityModel->authorizationresolution = $entity->authorizationresolution;
        $entityModel->authorizingentity = $entity->authorizingentity;
        $entityModel->description = $entity->description;
        $entityModel->isactive = $entity->isactive == 1;
        $entityModel->captionheader = $entity->captionheader;
        $entityModel->captionfooter = $entity->captionfooter;
        $entityModel->createdby = $entity->createdby;
        $entityModel->updatedby = $entity->updatedby;
        $entityModel->isdeleted = $entity->isdeleted == 1;


        if ($isNewRecord) {
            $entityModel->isDeleted = false;
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
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

        $authUser = $this->getAuthUser();
        $entityModel->isDeleted = true;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        $entityModel->save();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->code = $model->code;
            $entity->name = $model->name;
            $entity->amount = $model->amount;
            $entity->currency = $model->getCurrency();
            $entity->category = $model->getCategory();
            $entity->speciality = $model->getSpeciality();
            $entity->capacity = $model->capacity;
            $entity->hourduration = $model->hourduration;
            $entity->validitynumber = $model->validitynumber;
            $entity->validitytype = $model->getValiditytype();
            $entity->authorizationresolution = $model->authorizationresolution;
            $entity->authorizingentity = $model->authorizingentity;
            $entity->description = $model->description;
            $entity->isactive = $model->isactive;
            $entity->captionheader = $model->captionheader;
            $entity->captionfooter = $model->captionfooter;
            $entity->createdby = $model->createdby;
            $entity->updatedby = $model->updatedby;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;
            $entity->isdeleted = $model->isdeleted;


            return $entity;
        } else {
            return null;
        }
    }
}
