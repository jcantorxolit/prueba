<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-report', 'AdeN\Api\Modules\Customer\Report\Http\Controllers\CustomerReportController@show');
    Route::post('customer-report-save', 'AdeN\Api\Modules\Customer\Report\Http\Controllers\CustomerReportController@store');
    Route::post('customer-report-update', 'AdeN\Api\Modules\Customer\Report\Http\Controllers\CustomerReportController@update');
    Route::post('customer-report-delete', 'AdeN\Api\Modules\Customer\Report\Http\Controllers\CustomerReportController@destroy');
    Route::post('customer-report-import', 'AdeN\Api\Modules\Customer\Report\Http\Controllers\CustomerReportController@import');
    Route::post('customer-report-upload', 'AdeN\Api\Modules\Customer\Report\Http\Controllers\CustomerReportController@upload');
    Route::match(['post'], 'customer-report', 'AdeN\Api\Modules\Customer\Report\Http\Controllers\CustomerReportController@index');
	Route::match(['get', 'post'], 'customer-report/download', 'AdeN\Api\Modules\Customer\Report\Http\Controllers\CustomerReportController@download');