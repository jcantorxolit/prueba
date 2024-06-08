<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-report-calculated', 'AdeN\Api\Modules\Customer\ReportCalculatedField\Http\Controllers\CustomerReportCalculatedFieldController@show');
    Route::post('customer-report-calculated-save', 'AdeN\Api\Modules\Customer\ReportCalculatedField\Http\Controllers\CustomerReportCalculatedFieldController@store');
    Route::post('customer-report-calculated-update', 'AdeN\Api\Modules\Customer\ReportCalculatedField\Http\Controllers\CustomerReportCalculatedFieldController@update');
    Route::post('customer-report-calculated-delete', 'AdeN\Api\Modules\Customer\ReportCalculatedField\Http\Controllers\CustomerReportCalculatedFieldController@destroy');
    Route::post('customer-report-calculated-import', 'AdeN\Api\Modules\Customer\ReportCalculatedField\Http\Controllers\CustomerReportCalculatedFieldController@import');
    Route::post('customer-report-calculated-upload', 'AdeN\Api\Modules\Customer\ReportCalculatedField\Http\Controllers\CustomerReportCalculatedFieldController@upload');
    Route::match(['post'], 'customer-report-calculated', 'AdeN\Api\Modules\Customer\ReportCalculatedField\Http\Controllers\CustomerReportCalculatedFieldController@index');
	Route::match(['get', 'post'], 'customer-report-calculated/download', 'AdeN\Api\Modules\Customer\ReportCalculatedField\Http\Controllers\CustomerReportCalculatedFieldController@download');