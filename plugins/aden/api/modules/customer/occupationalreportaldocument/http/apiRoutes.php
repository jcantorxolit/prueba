<?php
    
	/**
     *Module: CustomerOccupationalReportDocument
     */
    Route::get('customer-occupational-report-document/get', 'AdeN\Api\Modules\Customer\OccupationalReportAlDocument\Http\Controllers\CustomerOccupationalReportDocumentController@show');
    Route::post('customer-occupational-report-document/save', 'AdeN\Api\Modules\Customer\OccupationalReportAlDocument\Http\Controllers\CustomerOccupationalReportDocumentController@store');
    Route::post('customer-occupational-report-document/delete', 'AdeN\Api\Modules\Customer\OccupationalReportAlDocument\Http\Controllers\CustomerOccupationalReportDocumentController@destroy');
    Route::post('customer-occupational-report-document/import', 'AdeN\Api\Modules\Customer\OccupationalReportAlDocument\Http\Controllers\CustomerOccupationalReportDocumentController@import');
    Route::post('customer-occupational-report-document/import-historical', 'AdeN\Api\Modules\Customer\OccupationalReportAlDocument\Http\Controllers\CustomerOccupationalReportDocumentController@importHistorical');
    Route::post('customer-occupational-report-document/upload', 'AdeN\Api\Modules\Customer\OccupationalReportAlDocument\Http\Controllers\CustomerOccupationalReportDocumentController@upload');
    Route::post('customer-occupational-report-document/export', 'AdeN\Api\Modules\Customer\OccupationalReportAlDocument\Http\Controllers\CustomerOccupationalReportDocumentController@export');
    Route::match(['get', 'post'], 'customer-occupational-report-document', 'AdeN\Api\Modules\Customer\OccupationalReportAlDocument\Http\Controllers\CustomerOccupationalReportDocumentController@index');
	Route::match(['get', 'post'], 'customer-occupational-report-document/download', 'AdeN\Api\Modules\Customer\OccupationalReportAlDocument\Http\Controllers\CustomerOccupationalReportDocumentController@download');