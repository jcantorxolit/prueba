<?php

namespace Wgroup\Classes;

use Exception;
use Log;
use Str;
use Wgroup\Models\AgentDocument;
use Wgroup\Models\AgentDocumentReporistory;
use Wgroup\Models\CustomerDocument;
use Wgroup\Models\CustomerDocumentReporistory;

class ServiceAgentDocument {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $agentDocumentRepository;

    function __construct() {
       // $this->customerRepository = new CustomerReporistory();
    }

    public function init() {
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $agentId = 0, $hideCanceled = true) {

        $model = new AgentDocument();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->agentDocumentRepository = new AgentDocumentReporistory($model);

        if ($perPage > 0) {
            $this->agentDocumentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_agent_document.id',
            'wg_agent_document.type',
            'wg_agent_document.classification',
            'wg_agent_document.description',
            'wg_agent_document.version',
            'wg_agent_document.agent_id',
            'wg_agent_document.status'
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
                    $this->agentDocumentRepository->sortBy($colName, $dir);
                } else {
                    $this->agentDocumentRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->agentDocumentRepository->sortBy('wg_agent_document.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_agent_document.agent_id', $agentId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_agent_document.type', $search);
            $filters[] = array('wg_agent_document.classification', $search);
            $filters[] = array('wg_agent_document.description', $search);
            $filters[] = array('wg_agent_document.version', $search);
            $filters[] = array('wg_agent_document.status', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_agent_document.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_agent_document.status', '0');
        }


        $this->agentDocumentRepository->setColumns(['wg_agent_document.*']);

        return $this->agentDocumentRepository->getFilteredsOptional($filters, false, "", $hideCanceled);
    }

    public function getCount($search = "", $agentId, $hideCanceled) {

        $model = new AgentDocument();
        $this->agentDocumentRepository = new AgentDocumentReporistory($model);

        $filters = array();

        $filters[] = array('wg_agent_document.agent_id', $agentId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_agent_document.type', $search);
            $filters[] = array('wg_agent_document.classification', $search);
            $filters[] = array('wg_agent_document.description', $search);
            $filters[] = array('wg_agent_document.version', $search);
            $filters[] = array('wg_agent_document.status', $search);
        }

        $this->agentDocumentRepository->setColumns(['wg_agent_document.*']);

        return $this->agentDocumentRepository->getFilteredsOptional($filters, true, "", $hideCanceled);
    }
}
