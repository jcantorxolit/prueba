<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-occupational-report-al', 'AdeN\Api\Modules\Customer\OccupationalReportAl\Http\Controllers\CustomerOccupationalReportController@show');
    Route::post('customer-occupational-report-al/save', 'AdeN\Api\Modules\Customer\OccupationalReportAl\Http\Controllers\CustomerOccupationalReportController@store');
    Route::post('customer-occupational-report-al/update', 'AdeN\Api\Modules\Customer\OccupationalReportAl\Http\Controllers\CustomerOccupationalReportController@update');
    Route::post('customer-occupational-report-al/delete', 'AdeN\Api\Modules\Customer\OccupationalReportAl\Http\Controllers\CustomerOccupationalReportController@destroy');
    Route::post('customer-occupational-report-al/import', 'AdeN\Api\Modules\Customer\OccupationalReportAl\Http\Controllers\CustomerOccupationalReportController@import');
    Route::post('customer-occupational-report-al/upload', 'AdeN\Api\Modules\Customer\OccupationalReportAl\Http\Controllers\CustomerOccupationalReportController@upload');
    Route::match(['post'], 'customer-occupational-report-al', 'AdeN\Api\Modules\Customer\OccupationalReportAl\Http\Controllers\CustomerOccupationalReportController@index');
	Route::match(['get', 'post'], 'customer-occupational-report-al/download', 'AdeN\Api\Modules\Customer\OccupationalReportAl\Http\Controllers\CustomerOccupationalReportController@download');