<?php


namespace AdeN\Api\Modules\Customer\VrSignatureCertificate;

use AdeN\Api\Classes\BaseRepository;

class SignatureCertificateVrRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new SignatureCertificateVrModel());
    }

    public function insertOrUpdate($entity)
    {
        $model = $this->model->where("customer_id", $entity->customerId)->first();
        if (!$model) {
            $model = $this->model->newInstance();
        } 
        $model->customerId = $entity->customerId;
        $model->fullName = $entity->fullName;
        $model->job = $entity->job;
        $model->isActive = $entity->isActive;
        $model->save();

        return $this->parseModelWithRelations($model->customerId);
    }

    public function delete($customerId)
    {
        $model = $this->model->where("customer_id", $customerId)->first();
        if ($model->signature) {
            $model->signature->delete();
        }
    }

    public function parseModelWithRelations($customerId)
    {
        $model = $this->model->where("customer_id", $customerId)->first();

        $result = new \stdClass();
        $result->fullName = $model ? $model->full_name : null;
        $result->customerId = $customerId;
        $result->job = $model ? $model->job : null;
        $result->isActive = $model ? $model->isActive : null;
        $result->signature = $model && $model->signature ? $model->signature->getTemporaryUrl() : null;
        $result->logo = $model && $model->logo ? $model->logo->getTemporaryUrl() : null;
        return $result;
    }
}
