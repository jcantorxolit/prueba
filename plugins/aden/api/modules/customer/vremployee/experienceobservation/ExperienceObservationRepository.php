<?php

namespace AdeN\Api\Modules\Customer\VrEmployee\ExperienceObservation;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Modules\Customer\VrEmployee\CustomerVrEmployeeRepository;
use DB;
use Exception;
use Log;
use Carbon\Carbon;

class ExperienceObservationRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ExperienceObservationModel());
    }

    public function insertOrUpdate($entity)
    {

        $isNewRecord = false;
        $authUser = $this->getAuthUser();
        if (!($entityModel = $this->query()->where("experience_code",$entity->experienceCode)
                ->where("customer_vr_employ_answer_experience_id",$entity->id)->first())) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerVrEmployAnswerExperienceId = $entity->id;
        $entityModel->observationType = $entity->observationType ? $entity->observationType->value :  null;
        $entityModel->observationValue = $entity->observationValue;
        $entityModel->experienceCode = $entity->experienceCode;
        
        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->createdAt = Carbon::now();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
        }
        
        $entityModel->save();

    }


}