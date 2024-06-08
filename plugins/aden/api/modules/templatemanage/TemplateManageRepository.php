<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\TemplateManage;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Wgroup\SystemParameter\SystemParameter;

class TemplateManageRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new TemplateManageModel());

        $this->service = new TemplateManageService();
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Fecha Creación", "name" => "createdAt"],
            ["alias" => "Tipo", "name" => "template"],
            ["alias" => "Fecha Publicación", "name" => "dateOf"],
            ["alias" => "Descripción", "name" => "description"],
            ["alias" => "Estado", "name" => "status"],
            ["alias" => "Versión", "name" => "version"],
        ];
    }

    public function all($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "id" => "wg_template_manage.id",
            "createdAt" => "wg_template_manage.created_at as createdAt",
            "template" => "wg_import_template.item AS template",
            "filename" => "wg_import_template.code AS filename",
            "dateof" => "wg_template_manage.dateOf",
            "description" => "wg_template_manage.description",
            "version" => "wg_template_manage.version",
            "status" => "wg_import_template_status.item AS status",
            "updatedBy" => "users.name AS updatedBy",
            "hasFile" => DB::raw("CASE WHEN system_files.id IS NULL THEN 0 ELSE 1 END hasFile"),
            "isActive" => "wg_template_manage.isActive",
            "templateValue" => "wg_template_manage.template as templateValue"
        ]);

        $showAllTemplate = CriteriaHelper::getMandatoryFilter($criteria, 'showAllTemplate');

        $showAllVersions = $showAllTemplate ? $showAllTemplate->value : false;

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_import_template')), function ($join) {
            $join->on('wg_template_manage.template', '=', 'wg_import_template.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_import_template_status')), function ($join) {
            $join->on('wg_template_manage.status', '=', 'wg_import_template_status.value');
        })->leftjoin('system_files', function ($join) {
            $join->on('wg_template_manage.id', '=', 'system_files.attachment_id');
            $join->where('system_files.field', 'template_file');
            $join->where('system_files.attachment_type', TemplateManageModel::class);
        })->leftjoin("users", function ($join) {
            $join->on('wg_template_manage.updatedBy', '=', 'users.id');
        })
            ->when(!$showAllVersions, function ($query) {
                $query->where('wg_template_manage.status', 'Publicado');
            });

        $this->applyCriteria($query, $criteria, ['showAllTemplate']);

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModelWithDocument($data, TemplateManageModel::class);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $lastVersion = $this->model->where('template', $entity->template->value)->max('version');
            $entityModel->version = $lastVersion + 1;
            $isNewRecord = true;
        }

        $entityModel->template = $entity->template ? $entity->template->value : null;
        $entityModel->description = $entity->description;
        $entityModel->status = 'Activo';

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        $this->publish($entityModel);

        return $entityModel;
    }

    public function publish($entityModel)
    {
        $instance = CmsHelper::getInstance();
        $attachment = $entityModel->template_file;
        if ($attachment) {

            $authUser = $this->getAuthUser();
            $template = $entityModel->getTemplate();
            $originalFilename = $template ? $template->code : $attachment->file_name;
            $filePath = "templates/$instance/$originalFilename";
            $file = $attachment->getContent();
            \Storage::disk('template')->put($filePath, $file);
            $this->model->where('template', $entityModel->template)->update([
                'status' => 'Activo',
                'isActive' => 0,
                'updatedBy' => $authUser ? $authUser->id : 1,
                'updated_at' => Carbon::now('America/Bogota')
            ]);
            $entityModel->status = 'Publicado';
            $entityModel->dateOf = Carbon::now('America/Bogota');
            $entityModel->isActive = true;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updated_at = Carbon::now('America/Bogota');
            $entityModel->save();
        }
    }

    public function upload($id, $files)
    {
        $model = $this->model->find($id);

        foreach ($files as $file) {
            $this->checkUploadPostBack($file, $model, 'template_file');
        }

        $model = $this->find($id);

        $this->publish($model);

        return $model;
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
            $entity->type = $model->getType();
            $entity->dateof = $model->dateof ? Carbon::parse($model->dateof) : null;
            $entity->name = $model->name;
            $entity->author = $model->author;
            $entity->subject = $model->subject;
            $entity->description = $model->description;
            $entity->keyword = $model->keyword;

            return $entity;
        } else {
            return null;
        }
    }
}
