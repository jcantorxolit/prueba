<?php


namespace AdeN\Api\Modules\Config\SignatureCertificateVr;

use AdeN\Api\Classes\BaseRepository;

class SignatureCertificateVrRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new SignatureCertificateVrModel());
    }

    public function insertOrUpdate($entity)
    {
        $model = $this->model->first();
        $model->fullName = $entity->fullName;
        $model->job = $entity->job;
        $model->save();

        return $this->parseModelWithRelations();
    }

    public function delete()
    {
        $model = $this->model->first();
        if ($model->signature) {
            $model->signature->delete();
        }
    }

    public function parseModelWithRelations()
    {
        $model = $this->model->first();

        $result = new \stdClass();
        $result->fullName = $model->full_name;
        $result->job = $model->job;
        $result->signature = $model->signature ? $model->signature->getTemporaryUrl() : null;
        $result->logo = $model->logo ? $model->logo->getTemporaryUrl() : null;
        return $result;
    }
}
