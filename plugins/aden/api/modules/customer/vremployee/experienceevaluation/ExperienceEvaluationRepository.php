<?php

namespace AdeN\Api\Modules\Customer\VrEmployee\ExperienceEvaluation;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Modules\Customer\VrEmployee\CustomerVrEmployeeModel;
use AdeN\Api\Modules\Customer\VrEmployee\CustomerVrEmployeeRepository;
use Carbon\Carbon;

class ExperienceEvaluationRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ExperienceEvaluationModel());
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;
        $authUser = $this->getAuthUser();
        if (!($entityModel = $this->query()->where("customer_vr_employee_id",$entity->customerVrEmployeeId)->first())) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerVrEmployeeId = $entity->customerVrEmployeeId;
//        $entityModel->observationType = $entity->observationType ? $entity->observationType->value :  null;
//        $entityModel->observationValue = $entity->observationValue;
        
        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->createdAt = Carbon::now();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
        }
        
        $entityModel->save();
        $entity->id = $entityModel->id;
        $this->generateCert($entityModel);
        CustomerVrEmployeeRepository::setFinish($entityModel->customerVrEmployeeId);

        return $entity;

    }

    public function generateCert($entityModel)
    {
        CustomerVrEmployeeRepository::generateCertificate($entityModel->customerVrEmployeeId);
    }

    public function deleteCertificatesBySessionImport(string $sessionId)
    {
        $vrEmployees = CustomerVrEmployeeModel::query()
            ->join('wg_customer_vr_employee_experience as exp', 'exp.customer_vr_employee_id', '=', 'wg_customer_vr_employee.id')
            ->join('wg_customer_vr_employee_staging as s', 's.id', '=', 'exp.staging_id')
            ->where('wg_customer_vr_employee.is_active', true)
            ->where('s.session_id', $sessionId)
            ->select('wg_customer_vr_employee.*')
            ->distinct()
            ->get();

        foreach ($vrEmployees as $vrEmployee) {
            if ($vrEmployee->document) {
                $vrEmployee->document->delete();
            }
        }
    }

    public function generateMassiveCertificates($criteria)
    {
        $result = (new CustomerVrEmployeeRepository)->allToGenerate($criteria);

        $index = 0;
        foreach ($result["data"] as $record) {
            $index++;
            $entity = new \stdClass();
            $entity->customerVrEmployeeId = $record->id;
            $this->insertOrUpdate($entity);
            if ($index <= 5) {
            }
        }

        return [
            "message" => "ok"
        ];
    }


}