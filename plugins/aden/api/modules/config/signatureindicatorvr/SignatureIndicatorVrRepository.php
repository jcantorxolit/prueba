<?php


namespace AdeN\Api\Modules\Config\SignatureIndicatorVr;

use AdeN\Api\Classes\BaseRepository;

class SignatureIndicatorVrRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new SignatureIndicatorVrModel());
    }

    public function insertOrUpdate($entity)
    {
        $model = $this->model->first();
        $model->text1 = $entity->text1;
        $model->text2 = $entity->text2;
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
        $result->text1 = $model->text1;
        $result->text2 = $model->text2;
        $result->signature = $model->signature ? $model->signature->getTemporaryUrl() : null;
        return $result;
    }
}
