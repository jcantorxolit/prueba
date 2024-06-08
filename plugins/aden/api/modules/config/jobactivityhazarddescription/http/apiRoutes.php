<?php
    
	/**
     *Module: ConfigJobActivityHazardDescription
     */
    Route::get('config-job-activity-hazard-description/get', 'AdeN\Api\Modules\Config\JobActivityHazardDescription\Http\Controllers\ConfigJobActivityHazardDescriptionController@show');
    Route::post('config-job-activity-hazard-description/save', 'AdeN\Api\Modules\Config\JobActivityHazardDescription\Http\Controllers\ConfigJobActivityHazardDescriptionController@store');
    Route::post('config-job-activity-hazard-description/delete', 'AdeN\Api\Modules\Config\JobActivityHazardDescription\Http\Controllers\ConfigJobActivityHazardDescriptionController@destroy');
    Route::post('config-job-activity-hazard-description/import', 'AdeN\Api\Modules\Config\JobActivityHazardDescription\Http\Controllers\ConfigJobActivityHazardDescriptionController@import');
    Route::post('config-job-activity-hazard-description/upload', 'AdeN\Api\Modules\Config\JobActivityHazardDescription\Http\Controllers\ConfigJobActivityHazardDescriptionController@upload');
    Route::match(['get', 'post'], 'config-job-activity-hazard-description', 'AdeN\Api\Modules\Config\JobActivityHazardDescription\Http\Controllers\ConfigJobActivityHazardDescriptionController@index');
	Route::match(['get', 'post'], 'config-job-activity-hazard-description/download', 'AdeN\Api\Modules\Config\JobActivityHazardDescription\Http\Controllers\ConfigJobActivityHazardDescriptionController@download');