<?php
    
	/**
     *Module: CustomerConfigJobActivityDocument
     */
    Route::get('customer-config-job-activity-document/get', 'AdeN\Api\Modules\Customer\ConfigJobActivityDocument\Http\Controllers\CustomerConfigJobActivityDocumentController@show');
    Route::post('customer-config-job-activity-document/save', 'AdeN\Api\Modules\Customer\ConfigJobActivityDocument\Http\Controllers\CustomerConfigJobActivityDocumentController@store');
    Route::post('customer-config-job-activity-document/delete', 'AdeN\Api\Modules\Customer\ConfigJobActivityDocument\Http\Controllers\CustomerConfigJobActivityDocumentController@destroy');
    Route::post('customer-config-job-activity-document/import', 'AdeN\Api\Modules\Customer\ConfigJobActivityDocument\Http\Controllers\CustomerConfigJobActivityDocumentController@import');
    Route::post('customer-config-job-activity-document/upload', 'AdeN\Api\Modules\Customer\ConfigJobActivityDocument\Http\Controllers\CustomerConfigJobActivityDocumentController@upload');
    Route::match(['get', 'post'], 'customer-config-job-activity-document', 'AdeN\Api\Modules\Customer\ConfigJobActivityDocument\Http\Controllers\CustomerConfigJobActivityDocumentController@index');
	Route::match(['get', 'post'], 'customer-config-job-activity-document/download', 'AdeN\Api\Modules\Customer\ConfigJobActivityDocument\Http\Controllers\CustomerConfigJobActivityDocumentController@download');