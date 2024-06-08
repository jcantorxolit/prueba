<?php
    
	/**
     *Module: ConfigJobActivityHazardType
     */
    Route::get('config-job-activity-hazard-type/get', 'AdeN\Api\Modules\Config\JobActivityHazardType\Http\Controllers\ConfigJobActivityHazardTypeController@show');
    Route::post('config-job-activity-hazard-type/save', 'AdeN\Api\Modules\Config\JobActivityHazardType\Http\Controllers\ConfigJobActivityHazardTypeController@store');
    Route::post('config-job-activity-hazard-type/delete', 'AdeN\Api\Modules\Config\JobActivityHazardType\Http\Controllers\ConfigJobActivityHazardTypeController@destroy');
    Route::post('config-job-activity-hazard-type/import', 'AdeN\Api\Modules\Config\JobActivityHazardType\Http\Controllers\ConfigJobActivityHazardTypeController@import');
    Route::post('config-job-activity-hazard-type/upload', 'AdeN\Api\Modules\Config\JobActivityHazardType\Http\Controllers\ConfigJobActivityHazardTypeController@upload');
    Route::match(['get', 'post'], 'config-job-activity-hazard-type', 'AdeN\Api\Modules\Config\JobActivityHazardType\Http\Controllers\ConfigJobActivityHazardTypeController@index');
	Route::match(['get', 'post'], 'config-job-activity-hazard-type/download', 'AdeN\Api\Modules\Config\JobActivityHazardType\Http\Controllers\ConfigJobActivityHazardTypeController@download');