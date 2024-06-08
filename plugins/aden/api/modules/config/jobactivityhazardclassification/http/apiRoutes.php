<?php
    
	/**
     *Module: ConfigJobActivityHazardClassification
     */
    Route::get('config-job-activity-hazard-classification/get', 'AdeN\Api\Modules\Config\JobActivityHazardClassification\Http\Controllers\ConfigJobActivityHazardClassificationController@show');
    Route::post('config-job-activity-hazard-classification/save', 'AdeN\Api\Modules\Config\JobActivityHazardClassification\Http\Controllers\ConfigJobActivityHazardClassificationController@store');
    Route::post('config-job-activity-hazard-classification/delete', 'AdeN\Api\Modules\Config\JobActivityHazardClassification\Http\Controllers\ConfigJobActivityHazardClassificationController@destroy');
    Route::post('config-job-activity-hazard-classification/import', 'AdeN\Api\Modules\Config\JobActivityHazardClassification\Http\Controllers\ConfigJobActivityHazardClassificationController@import');
    Route::post('config-job-activity-hazard-classification/upload', 'AdeN\Api\Modules\Config\JobActivityHazardClassification\Http\Controllers\ConfigJobActivityHazardClassificationController@upload');
    Route::match(['get', 'post'], 'config-job-activity-hazard-classification', 'AdeN\Api\Modules\Config\JobActivityHazardClassification\Http\Controllers\ConfigJobActivityHazardClassificationController@index');
	Route::match(['get', 'post'], 'config-job-activity-hazard-classification/download', 'AdeN\Api\Modules\Config\JobActivityHazardClassification\Http\Controllers\ConfigJobActivityHazardClassificationController@download');