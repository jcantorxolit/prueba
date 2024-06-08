<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

 namespace AdeN\Api\Modules\Customer\OccupationalReportAlDocument;

use AdeN\Api\Classes\Criteria;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\EmailHelper;
use AdeN\Api\Helpers\StringHelper;
use AdeN\Api\Modules\User\Message\UserMessageRepository;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use DB;


class CustomerOccupationalReportDocumentJob
{
    protected $service;

    public function __construct()
    {    
        $this->service = new CustomerOccupationalReportDocumentService();
    }

    public function fire($job, $data)
    {
        \Log::info("Iniciar fire job");
    
        $criteria = json_decode (json_encode ( $data["criteria"]), FALSE);
        $newCriteria = new Criteria();
        $newCriteria->mandatoryFilters = $criteria->mandatoryFilters;
        $newCriteria->filter = $criteria->filter;

        $data = $this->service->getExportData($newCriteria);

        $excelFilename = 'GUIA_DOCUMENTOS_REPORTES_AT_' . Carbon::now()->timestamp;
        ExportHelper::excelStorage($excelFilename, 'GUIA', $data['excel']);

        $zipFilename = 'DOCUMENTOS_DOCUMENTOS_REPORTES_AT_' . Carbon::now()->timestamp . '.zip';
        $zipFullPath = CmsHelper::getStorageDirectory('zip/exports') . '/' . $zipFilename;

        if (!CmsHelper::makeDir(CmsHelper::getStorageDirectory('zip/exports'))) {
            throw new \Exception("Can create folder", 403);
        }

        $zipData = array_merge($data['zip'], [[
            'fullPath' => CmsHelper::getStorageDirectory('excel/exports') . '/' . $excelFilename . ".xlsx",
            "filename" => $excelFilename . ".xlsx"
        ]]);

        ExportHelper::zipFileSystemStream($zipFullPath, $zipData);        

        $url = CmsHelper::getUrlSite() . CmsHelper::getPublicDirectory('zip/exports/') . $zipFilename;

        $this->sendNotification($criteria, $url);

        $job->delete();

        \Log::info("Finalizar fire job");
    }

    public function sendNotification($criteria, $url)
    {               
        $criteria->module = "DOCUMENTOS REPORTES_AT";
        $criteria->url = $url;        
        EmailHelper::notifyExportAttachment($criteria);
    }
}
