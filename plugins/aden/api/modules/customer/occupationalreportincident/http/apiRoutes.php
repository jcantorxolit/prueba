<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-occupational-report-incident', 'AdeN\Api\Modules\Customer\OccupationalReportIncident\Http\Controllers\CustomerOccupationalReportIncidentController@show');
    Route::post('customer-occupational-report-incident/save', 'AdeN\Api\Modules\Customer\OccupationalReportIncident\Http\Controllers\CustomerOccupationalReportIncidentController@store');
    Route::post('customer-occupational-report-incident/update', 'AdeN\Api\Modules\Customer\OccupationalReportIncident\Http\Controllers\CustomerOccupationalReportIncidentController@update');
    Route::post('customer-occupational-report-incident/delete', 'AdeN\Api\Modules\Customer\OccupationalReportIncident\Http\Controllers\CustomerOccupationalReportIncidentController@destroy');
    Route::post('customer-occupational-report-incident/import', 'AdeN\Api\Modules\Customer\OccupationalReportIncident\Http\Controllers\CustomerOccupationalReportIncidentController@import');
    Route::post('customer-occupational-report-incident/upload', 'AdeN\Api\Modules\Customer\OccupationalReportIncident\Http\Controllers\CustomerOccupationalReportIncidentController@upload');
    Route::match(['post'], 'customer-occupational-report-incident', 'AdeN\Api\Modules\Customer\OccupationalReportIncident\Http\Controllers\CustomerOccupationalReportIncidentController@index');
	Route::match(['get', 'post'], 'customer-occupational-report-incident/download', 'AdeN\Api\Modules\Customer\OccupationalReportIncident\Http\Controllers\CustomerOccupationalReportIncidentController@download');