<?php

namespace AdeN\Api\Modules\Customer\VrEmployee\Experience;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\VrEmployee\CustomerVrEmployeeModel;
use DB;
use Exception;
use Log;
use Carbon\Carbon;

class ExperienceRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ExperienceModel());
        $this->service = new ExperienceService();
    }

    public function insertOrUpdate($experienceList)
    {

        foreach ($experienceList->experienceList as $experience) {
            foreach ($experience->scenes as $scene) {

                $isNewRecord = false;
                $authUser = $this->getAuthUser();
                if (!($entityModel = $this->find($scene->id))) {
                    $entityModel = $this->model->newInstance();
                    $isNewRecord = true;
                }

                $entityModel->id = $scene->id;
                $entityModel->customerVrEmployeeId = $experienceList->vrEmployeeId;
                $entityModel->experienceCode = $scene->experienceValue;
                $entityModel->experienceSceneCode = $scene->sceneValue;
                $entityModel->application = $scene->application->value;
                $entityModel->justification = $scene->justification;

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
    }

    // public function insertOrUpdateConfig($experienceList)
    // {
    //     foreach ($experienceList->experienceList as $experience) {
    //         foreach ($experience->scenes as $scene) {
    //             $scene->application->value = !empty($experience->isActive) && $experience->isActive ? "SI" : "NO";
    //         }
    //     }

    //     return $experienceList;
    // }

    public static function getExperienceList($criteria, $options)
    {
        $reposity = new self;
        return $reposity->service->getExperienceList($criteria, $options);
    }

    public static function getEmployeeExperienceList($criteria)
    {
        $reposity = new self;
        return $reposity->service->getEmployeeExperienceList($criteria);
    }

    public static function getEmployeeExperiencePeriodList($criteria)
    {
        $reposity = new self;
        return $reposity->service->getEmployeeExperiencePeriodList($criteria);
    }

    public static function getEmployeeExperienceQuery($criteria)
    {
        $reposity = new self;
        return $reposity->service->getEmployeeExperienceQuery($criteria);
    }

    public static function getEmployeeExperienceFilterList($criteria)
    {
        $reposity = new self;
        return $reposity->service->getEmployeeExperienceFilterList($criteria);
    }

    public static function getStats($criteria)
    {
        $reposity = new self;
        return $reposity->service->getStats($criteria);
    }


}
