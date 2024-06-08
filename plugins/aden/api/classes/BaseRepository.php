<?php

/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 4/25/2016
 * Time: 8:57 PM
 */

namespace AdeN\Api\Classes;


use AdeN\Api\Helpers\SqlHelper;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use October\Rain\Support\Collection as OctoberCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Auth;

use Excel;
use Illuminate\Support\Facades\Input;
use Log;
use Request;
use Response;
use Session;
use System\Classes\SystemException;
use Validator;
use System\Models\File;
use AdeN\Api\Helpers\CmsHelper;
use October\Rain\Exception\ValidationException;

class BaseRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $service;

    protected $columns = [];
    protected $filterColumns = [];
    protected $sortColumns = [];
    protected $currentSort = [];

    protected $pageSize = 15;

    public function __construct($model)
    {
        $this->makeModel($model);
    }

    protected function getColumns()
    {
        return $this->columns;
    }

    protected function setColumns($columns)
    {
        $this->filterColumns = $columns;

        foreach ($columns as $column) {
            $this->columns[] = $column;
        }
    }

    protected function getSortColumns()
    {
        return $this->sortColumns;
    }

    protected function setSortColumns($columns)
    {
        if (!is_array($columns)) {
            return $this;
        }

        foreach ($columns as $column) {
            $this->currentSort[] = $this->parseSortColumn($column);
        }
    }


    /**
     * Sets the number of items displayed per page of results
     *
     * @param integer $pageSize The number of items to display per page
     *
     * @return Eloquent Repository The current instance
     */
    protected function paginate($pageSize)
    {
        $this->pageSize = (int)$pageSize;

        return $this;
    }

    /**
     * Creates a new QueryBuilder instance and applies the current sorting
     * @return Builder
     */
    protected function query($query = null)
    {
        $query = $query ?: $this->model->newQuery();

        foreach ($this->sortColumns as $sort) {
            if (property_exists($sort, "column")) {
                $fields = array_keys($this->filterColumns);
                if (count($fields) >= $sort->column) {
                    $sortColumnKey = $fields[$sort->column];
                    $sortColumn = $this->filterColumns[$sortColumnKey];
                    $query->orderBy(SqlHelper::getPreparedOrderField($sortColumn), $sort->dir);
                }
            } else if (property_exists($sort, "field")) {
                $sortColumn = $this->filterColumns[$sort->field];
                $query->orderBy(SqlHelper::getPreparedOrderField($sortColumn), $sort->dir);
            }
        }

        return $query;
    }

    /**
     * @param $id
     * @param array $columns
     *
     * @return mixed
     * @throws Exception
     */
    public function find($id, $columns = ['*'])
    {
        return $this->model->find($id, $columns);
    }

    /**
     * Retrieves a set of items based on a single value
     *
     * @param string $fieldName The name of the field to match
     * @param string $fieldValue The value of the field to match
     * @param string $fieldOrderBy The value of the field to order
     *
     * @return Paginator|Collection
     */
    public function findByField($fieldName, $fieldValue, $fieldOrderBy = null)
    {
        if ($fieldOrderBy == null) {
            $query = $this->query()->where($fieldName, $fieldValue);
        } else {
            $query = $this->query()->where($fieldName, $fieldValue)->orderBy($fieldOrderBy, 'asc');
        }

        return ($this->pageSize > 0) ? $this->parseModel($query->paginate($this->pageSize, ['*'])) : $this->parseModel($query->get(['*']));
    }

    public function all($criteria)
    {
        $this->parseCriteria($criteria);

        $query = $this->query();

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'or');
                        } catch (Exception $exc) {
                            Log::error($exc->getMessage());
                        }
                    }
                });
            }
        }

        $result["total"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
        $result["data"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        return $result;
    }


    protected function addColumn($column)
    {
        $this->filterColumns[] = $column;
        $this->columns[] = $column;

        return $this;
    }

    protected function addSortColumn($column, $dir = 'ASC')
    {
        $sort = new \stdClass();
        $sort->field = $column;
        $sort->dir = $dir;

        $this->sortColumns[] = $sort;

        return $this;
    }

    protected function clearColumns()
    {
        $this->filterColumns = [];
        $this->columns = [];

        return $this;
    }

    protected function parseModel($data)
    {
        if ($data instanceof Paginator || $data instanceof LengthAwarePaginator) {
            $models = $data->all();
        } else {
            $models = $data;
        }

        $modelClass = get_class($this->model);

        if (is_array($models) || $models instanceof Collection || $models instanceof OctoberCollection) {
            $parsed = array();
            foreach ($models as $model) {

                if ($model instanceof $modelClass) {
                    $parsed[] = $this->checkModel($model);
                } else {
                    $parsed[] = $this->checkModel($model);
                }
            }

            return $parsed;
        } else if ($data instanceof $modelClass) {
            return $data;
        } else {
            return $data;
        }
    }

    protected function parseModelWithDocument($data, $className, $property = null, $field = 'document', $modelIdProperty = 'id')
    {
        if ($data instanceof Paginator || $data instanceof LengthAwarePaginator) {
            $models = $data->all();
        } else {
            $models = $data;
        }

        $modelClass = get_class($this->model);
        $modelInstance = new $className;

        if (is_array($models) || $models instanceof Collection || $models instanceof OctoberCollection) {
            $parsed = array();
            $uids = $this->getUidsFromModel($models, $modelIdProperty);

            if (count($uids) > 0) {
                $uniqueId = str_replace('-', '_', $this->guidv4());
                $temporaryTableName = "attachments_{$uniqueId}";
                traceSql();
                DB::statement("DROP TEMPORARY TABLE IF EXISTS {$temporaryTableName}");
                DB::statement("CREATE TEMPORARY TABLE IF NOT EXISTS {$temporaryTableName} (id INT NOT NULL,PRIMARY KEY (`id`))");
                $temporaryTablevalues = array_map(function ($id) {
                    return "({$id})";
                }, $uids);
                $temporaryTableData = implode(',', $temporaryTablevalues);
                DB::statement("INSERT INTO {$temporaryTableName} VALUES {$temporaryTableData}");

                $documents = \System\Models\File::join($temporaryTableName, "$temporaryTableName.id", "=", "system_files.attachment_id")
                    ->where('attachment_type', $className)
                    ->where('field', $field)
                    ->get();


                DB::statement("DROP TEMPORARY TABLE IF EXISTS {$temporaryTableName}");

                // $documents = \System\Models\File::where('attachment_type', $className)
                //     ->whereIn('attachment_id', $uids)
                //     ->where('field', $field)
                //     ->get();

                foreach ($models as $model) {
                    $model->documentUrl = null;
                    $document = $documents->firstWhere('attachment_id', $model->{$modelIdProperty});
                    if ($property == null) {
                        $model->documentUrl = $document ? $document->getTemporaryUrl() : null;
                    } else {
                        $model->{$property} = $document ? $document->getTemporaryUrl() : null;
                    }

                    if ($model instanceof $modelClass) {
                        $parsed[] = $this->checkModel($model);
                    } else {
                        $parsed[] = $this->checkModel($model);
                    }
                }
            }

            return $parsed;
        } else if ($data instanceof $modelClass) {
            return $data;
        } else {
            return $data;
        }
    }

    private function guidv4($data = null)
    {
        // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function getUidsFromModel($models, $modelIdProperty)
    {
        $uids = [];
        foreach ($models as $model) {
            $uids[] = $model->{$modelIdProperty};
        }
        return $uids;
    }

    private function checkModel($model)
    {

        if (isset($model->isActive)) {
            $model->isActive = $model->isActive == 1;
        }

        if (isset($model->isMandatory)) {
            $model->isMandatory = $model->isMandatory == 1;
        }

        if (isset($model->isSpecial)) {
            $model->isSpecial = $model->isSpecial == 1;
        }

        if (isset($model->permanent)) {
            $model->permanent = $model->permanent == 1;
        }

        if (isset($model->createdAt)) {
            $model->createdAt = $model->createdAt ? Carbon::parse($model->createdAt)->timezone('America/Bogota') : null;
        }

        if (isset($model->updatedAt)) {
            $model->updatedAt = $model->updatedAt ? Carbon::parse($model->updatedAt)->timezone('America/Bogota') : null;
        }

        if (isset($model->start)) {
            $model->startDateFormat = $model->start ? Carbon::parse($model->start)->format('d/m/Y') : null;
        }

        if (isset($model->end)) {
            $model->endDateFormat = $model->end ? Carbon::parse($model->end)->format('d/m/Y') : null;
        }

        if (isset($model->registrationDeadline)) {
            $model->registrationDeadline = $model->registrationDeadline ? Carbon::parse($model->registrationDeadline)->format('d/m/Y') : null;
        }

        if (isset($model->deactivatedAt)) {
            $model->deactivatedAt = $model->deactivatedAt ? Carbon::parse($model->deactivatedAt)->format("d.m.Y H:i") : null;
        }

        return $model;
    }

    protected function makeModel($model)
    {
        $this->model = $model;
    }

    protected function parseCriteria($criteria)
    {
        if ($criteria != null) {
            $this->paginate($criteria->pageSize);
            $this->sortColumns = $criteria->sorts;
        } else {
            $this->paginate(0);
        }


        //$this->model->getConnection()->getPaginator()->setCurrentPage(($criteria != null) ? $criteria->currentPage : 1);


        Paginator::currentPageResolver(function () use ($criteria) {
            return ($criteria != null) ? $criteria->currentPage : 1;
        });
    }

    protected function applyCriteria($query, $criteria, $excludeMandatoryFields = [])
    {
        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $applyFilter = ($excludeMandatoryFields == null || count($excludeMandatoryFields) == 0 || !in_array($item->field, $excludeMandatoryFields));
                    if ($applyFilter) {
                        switch ($item->operator) {
                            case 'in':
                                $query->whereIn(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getPreparedData($item), 'and');
                                break;

                            default:
                                $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                                break;
                        }
                    }
                }
            }


            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), SqlHelper::getCondition($item));
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        return $this;
    }

    protected function get($query, $criteria)
    {
        //$data = ($this->pageSize > 0) ? $query->paginate($this->pageSize) : $query->get($this->columns);
        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        if ($data instanceof Paginator || $data instanceof LengthAwarePaginator) {
            $total = $data->total();
        } else if ($data instanceof Collection || $data instanceof OctoberCollection) {
            $total = $data->count();
        } else if (is_array($data)) {
            $total = count($data);
        } else {
            $total = 0;
        }

        $result["data"] = $this->parseModel($data);
        $result["total"] = $total;
        $result["recordsTotal"] = $total;
        $result["recordsFiltered"] = $total;
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    private function parseSortColumn($column)
    {
        if (is_array($column)) {
            return $column;
        }

        return array($column, "ASC");
    }

    protected function getAuthUser()
    {
        return Auth::getUser();
    }

    protected function findAuthUser($id)
    {
        return DB::table('users')->where('id')->first();
    }

    protected function formatDbDate($field, $format = '%d.%m.%Y')
    {
        return DB::raw('DATE_FORMAT(' . $field . ', "' . $format . '") as someDate');
    }

    public function checkUploadPostBack($uploadedFile, $model, $fieldName = "document")
    {
        $uploadedFileName = null;
        $result = array();
        try {
            //  $uploadedFile = Input::file('file');

            if ($uploadedFile)
                $uploadedFileName = $uploadedFile->getClientOriginalName();

            Log::info($uploadedFileName);
            Log::info($uploadedFile->getClientMimeType());

            $validationRules = ['max:' . File::getMaxFilesize()];
            $validationRules[] = 'mimes:' . CmsHelper::getMimeTypes();

            $validation = Validator::make(
                ['file_data' => $uploadedFile],
                ['file_data' => $validationRules]
            );

            if ($validation->fails())
                throw new ValidationException($validation);

            if (!$uploadedFile->isValid())
                throw new SystemException('File is not valid');

            $fileRelation = $model->{$fieldName}();

            $file = new File();
            $file->data = $uploadedFile;
            $file->is_public = true;
            $file->save();

            $fileRelation->add($file);

            $result = [
                'id' => $file->id,
                'file' => $uploadedFileName,
                'path' => $file->getDiskPath(),
                'contentType' => $file->content_type
            ];
        } catch (Exception $ex) {
            \Log::error($ex);
            $message = $uploadedFileName ? 'Error uploading file "%s". %s' : 'Error uploading file. %s';

            $result = [
                'error' => sprintf($message, $uploadedFileName, $ex->getMessage()),
                'file' => $uploadedFileName
            ];
        }

        return $result;
    }

    public function findSystemFile($id)
    {
        return File::find($id);
    }

    public function getDownloadHeaders($document)
    {
        $headers = array(
            'Content-Type:' . $document->content_type,
            'Content-Disposition:attachment; filename="' . $document->file_name . '"',
            'Content-Transfer-Encoding:binary',
            'Content-Length:' . $document->file_size,
        );

        return $headers;
    }

    public function getMonthName($month)
    {
        $months = [
            "1" => "Enero",
            "2" => "Febrero",
            "3" => "Marzo",
            "4" => "Abril",
            "5" => "Mayo",
            "6" => "Junio",
            "7" => "Julio",
            "8" => "Agosto",
            "9" => "Septiembre",
            "10" => "Octubre",
            "11" => "Noviembre",
            "12" => "Diciembre",
        ];

        $result = $month;

        if (array_key_exists($month, $months))
        {
            $result = $months[$month];
        }

        return $result;
    }
}
