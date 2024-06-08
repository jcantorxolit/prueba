<?php

	/**
     *Module: CustomerEmployeeDocument
     */
    Route::post('customer-employee-document/get', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@show');
    Route::post('customer-employee-document/save', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@store');
    Route::post('customer-employee-document/update', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@update');
    Route::post('customer-employee-document/delete', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@destroy');
    Route::post('customer-employee-document/import', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@import');
    Route::post('customer-employee-document/upload', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@upload');
    Route::post('customer-employee-document/export', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@export');
    Route::post('customer-employee-document/export-by-type', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@exportByType');
    Route::get('customer-employee-document/download-template', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@downloadTemplate');
    Route::match(['post'], 'customer-employee-document', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@index');
    Route::match(['post'], 'customer-employee-document-filter', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@indexFilter');
    Route::match(['post'], 'customer-employee-document-expiration', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@indexExpiration');
    Route::match(['post'], 'customer-employee-document-required', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@indexRequired');
    Route::match(['post'], 'customer-employee-document-required-critical', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@indexRequiredCritical');
    Route::match(['post'], 'customer-employee-document-export', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@indexExport');
    Route::match(['get'], 'customer-employee-document-expiration-export', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@indexExpirationExport');
	Route::match(['get', 'post'], 'customer-employee-document/download', 'AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers\CustomerEmployeeDocumentController@download');
