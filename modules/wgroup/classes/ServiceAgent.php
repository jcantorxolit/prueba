<?php

namespace Wgroup\Classes;

use DB;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Str;
use Wgroup\Models\Agent;
use Wgroup\Models\AgentRepository;

class ServiceAgent
{

    protected static $instance;
    protected $sessionKey = 'service_agent';
    protected $agentRepository;

    function __construct()
    {
        //$this->agentRepository = new CustomerReporistory();
    }

    public function init()
    {
        parent::init();
    }

    public function getAll($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "")
    {

        $model = new Agent();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->agentRepository = new AgentRepository($model);

        if ($perPage > 0) {
            $this->agentRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'wg_agent.documentType',
            'wg_agent.documentNumber',
            'wg_agent.name',
            'wg_agent.type',
            'wg_agent.gender'
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

                if ($dir == null || $dir == "") {
                    $dir = " asc ";
                }

                if ($i == 0) {
                    $this->agentRepository->sortBy($colName, $dir);
                } else {
                    $this->agentRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->agentRepository->sortBy('wg_agent.lastName', 'asc');
        }

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_agent.type', $search);
            $filters[] = array('wg_agent.documentType', $search);
            $filters[] = array('wg_agent.documentNumber', $search);
            $filters[] = array('wg_agent.name', $search);
            $filters[] = array('wg_agent.gender', $search);
            $filters[] = array('agent_type.item', $search);
            //$filters[] = array('agentType.item', $search);
            //$filters[] = array('tipod.item', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_agent.active', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_agent.active', '0');
        }


        $this->agentRepository->setColumns(['wg_agent.*']);

        return $this->agentRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllRecordsCount($search = "")
    {

        $model = new Agent();
        $this->agentRepository = new AgentRepository($model);

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_agent.type', $search);
            $filters[] = array('wg_agent.documentType', $search);
            $filters[] = array('wg_agent.documentNumber', $search);
            $filters[] = array('wg_agent.name', $search);
            $filters[] = array('wg_agent.gender', $search);
            $filters[] = array('agent_type.item', $search);
            //$filters[] = array('agentType.item', $search);
            //$filters[] = array('tipod.item', $search);
        }

        $this->agentRepository->setColumns(['wg_agent.*']);

        return $this->agentRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllByCustomer($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId)
    {

        $model = new Agent();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->agentRepository = new AgentRepository($model);

        if ($perPage > 0) {
            $this->agentRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'wg_agent.documentType',
            'wg_agent.documentNumber',
            'wg_agent.name',
            'wg_agent.type',
            'wg_agent.gender'
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

                if ($dir == null || $dir == "") {
                    $dir = " asc ";
                }

                if ($i == 0) {
                    $this->agentRepository->sortBy($colName, $dir);
                } else {
                    $this->agentRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->agentRepository->sortBy('wg_agent.lastName', 'asc');
        }

        $filters = array();

        $filters[] = array('wg_customer_agent.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_agent.type', $search);
            $filters[] = array('wg_agent.documentType', $search);
            $filters[] = array('wg_agent.documentNumber', $search);
            $filters[] = array('wg_agent.name', $search);
            $filters[] = array('wg_agent.gender', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_agent.active', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_agent.active', '0');
        }


        $this->agentRepository->setColumns(['wg_agent.*']);

        return $this->agentRepository->getFilteredOptional($filters, false, "");
    }

    public function getAllByCustomerCount($search = "", $customerId)
    {

        $model = new Agent();
        $this->agentRepository = new AgentRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_agent.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_agent.type', $search);
            $filters[] = array('wg_agent.documentType', $search);
            $filters[] = array('wg_agent.documentNumber', $search);
            $filters[] = array('wg_agent.name', $search);
            $filters[] = array('wg_agent.gender', $search);
        }

        $this->agentRepository->setColumns(['wg_agent.*']);

        return $this->agentRepository->getFilteredOptional($filters, true, "");
    }
}
