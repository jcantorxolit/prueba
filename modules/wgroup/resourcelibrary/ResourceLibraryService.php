<?php

namespace Wgroup\ResourceLibrary;

use DB;
use Exception;
use Log;
use Str;

class ResourceLibraryService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $repository;

    function __construct()
    {
        // $this->customerRepository = new CustomerReporistory();
    }

    public function init()
    {
        parent::init();
    }

    /**
     * @param $search
     * @param int $perPage
     * @param int $currentPage
     * @param array $sorting
     * @param string $typeFilter
     * @return mixed
     */
    public function getAll($search, $perPage = 10, $currentPage = 0, $sorting = array(), $operation = "", $audit = null)
    {

        $model = new ResourceLibrary();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new ResourceLibraryRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_resource_library.id',
            'wg_resource_library.type',
            'wg_resource_library.dateOf',
            'wg_resource_library.name',
            'wg_resource_library.author',
            'wg_resource_library.subject',
            'wg_resource_library.description',
            'wg_resource_library.isActive'
        ];

        $i = 0;

        foreach ($sorting as $key => $value) {
            try {

                if (isset($value["column"]) === false) {
                    continue;
                }

                $col = $value["column"];
                $dir = $value["dir"];

                $colName = $columns[$col];

                if ($colName == "") {
                    continue;
                }

                if ($colName == "wg_resource_library.id") {
                    continue;
                }

                if ($dir == null || $dir == "") {
                    $dir = " asc ";
                }

                if ($i == 0) {
                    $this->repository->sortBy($colName, $dir);
                } else {
                    $this->repository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        $filters = array();

        if ($operation == "client") {
            $filters[] = array('wg_resource_library.isActive', 1);
        }

        if (strlen(trim($search)) > 0) {
            $filters[] = array('resource_library_type.item', $search);
            $filters[] = array('wg_resource_library.dateOf', $search);
            $filters[] = array('wg_resource_library.name', $search);
            $filters[] = array('wg_resource_library.subject', $search);
            $filters[] = array('wg_resource_library.description', $search);
        }


        $this->repository->setColumns(['wg_resource_library.*']);

        return $this->repository->getFilteredsOptional($filters, false, "", $audit);
    }

    public function getCount($search = "", $operation = "", $audit = null)
    {

        $model = new ResourceLibrary();

        $this->repository = new ResourceLibraryRepository($model);

        $filters = array();

        if ($operation == "client") {
            $filters[] = array('wg_resource_library.isActive', 1);
        }

        if (strlen(trim($search)) > 0) {
            $filters[] = array('resource_library_type.item', $search);
            $filters[] = array('wg_resource_library.dateOf', $search);
            $filters[] = array('wg_resource_library.name', $search);
            $filters[] = array('wg_resource_library.subject', $search);
            $filters[] = array('wg_resource_library.description', $search);
        }

        $this->repository->setColumns(['wg_resource_library.*']);

        return $this->repository->getFilteredsOptional($filters, true, "", $audit);
    }


    public function getAllCategory($type, $keyword, $perPage = 10, $currentPage = 0)
    {

        $model = new ResourceLibrary();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new ResourceLibraryRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        $filters = array();

        $filters[] = array('wg_resource_library.isActive', 1);

        if (strlen(trim($type)) > 0) {
            $filters[] = array('wg_resource_library.type', $type);
        }

        if (strlen(trim($keyword)) > 0) {
            $filters[] = array('wg_resource_library.keyword', $keyword);
            $filters[] = array('wg_resource_library.name', $keyword);
            $filters[] = array('wg_resource_library.author', $keyword);
            $filters[] = array('wg_resource_library.subject', $keyword);
            $filters[] = array('wg_resource_library.description', $keyword);
        }

        $this->repository->setColumns(['wg_resource_library.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCountCategory($type, $keyword)
    {

        $model = new ResourceLibrary();

        $this->repository = new ResourceLibraryRepository($model);

        $filters = array();

        $filters[] = array('wg_resource_library.isActive', 1);

        if (strlen(trim($type)) > 0) {
            $filters[] = array('wg_resource_library.type', $type);
        }

        if (strlen(trim($keyword)) > 0) {
            $filters[] = array('wg_resource_library.keyword', $keyword);
            $filters[] = array('wg_resource_library.name', $keyword);
            $filters[] = array('wg_resource_library.author', $keyword);
            $filters[] = array('wg_resource_library.subject', $keyword);
            $filters[] = array('wg_resource_library.description', $keyword);
        }

        $this->repository->setColumns(['wg_resource_library.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }
}
