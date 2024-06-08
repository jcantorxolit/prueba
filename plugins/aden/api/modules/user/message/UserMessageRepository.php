<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\User\Message;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class UserMessageRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new UserMessageModel());

        $this->service = new UserMessageService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_user_message.id",
            "userId" => "wg_user_message.user_id",
            "from" => "wg_user_message.from",
            "to" => "wg_user_message.from AS to",
            "subject" => "wg_user_message.subject",
            "content" => "wg_user_message.content",
            "isReaded" => "wg_user_message.is_readed",
            "date" => "wg_user_message.created_at AS date",
            "readedAt" => "wg_user_message.readed_at",
        ]);

        $this->parseCriteria($criteria);

        $this->paginate(0);

        $query = $this->query()
            ->orderBy('wg_user_message.created_at', 'DESC');

        $this->applyCriteria($query, $criteria);

        $data = $this->get($query, $criteria);

        $data['data'] = array_map(function ($item) {
            return [
                "id" => $item->id,
                "from" => $item->from,
                "to" => $item->to,
                "subject" => $item->subject,
                "content" => $item->content,
                "isReaded" => $item->isReaded == 1,
                "date" => $item->date ? Carbon::parse($item->date)->timezone('America/Bogota')->format("d/m/Y H:i:s") : null
            ];
        }, $data['data']);

        return $data;
    }

    public function insertOrUpdate($entity)
    {
        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
        }

        $entityModel->userId = $entity->userId;
        $entityModel->from = $entity->from;
        $entityModel->subject = $entity->subject;
        $entityModel->content = $entity->content;
        $entityModel->isReaded = $entity->isReaded == 1;
        $entityModel->readedAt = $entity->readedAt ? Carbon::parse($entity->readedAt)->timezone('America/Bogota') : null;

        $entityModel->save();

        return $entityModel;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        return $entityModel->delete();
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            $model->isReaded = true;
            $model->readedAt = Carbon::now();
            $model->save();

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->from = $model->from;
            $entity->to = ($user = $model->user) ? $user->name . ' ' . $user->surname : null;
            $entity->subject = $model->subject;
            $entity->content = $model->content;
            $entity->isReaded = $model->isReaded == 1;
            $entity->date = $model->createdAt ? Carbon::parse($model->createdAt)->timezone('America/Bogota')->format("d/m/Y H:i:s") : null;

            return $entity;
        } else {
            return null;
        }
    }
}
