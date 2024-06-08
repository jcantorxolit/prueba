<?php
    
	/**
     *Module: ConfigJobActivityHazardEffect
     */
    Route::get('config-job-activity-hazard-effect/get', 'AdeN\Api\Modules\Config\JobActivityHazardEffect\Http\Controllers\ConfigJobActivityHazardEffectController@show');
    Route::post('config-job-activity-hazard-effect/save', 'AdeN\Api\Modules\Config\JobActivityHazardEffect\Http\Controllers\ConfigJobActivityHazardEffectController@store');
    Route::post('config-job-activity-hazard-effect/delete', 'AdeN\Api\Modules\Config\JobActivityHazardEffect\Http\Controllers\ConfigJobActivityHazardEffectController@destroy');
    Route::post('config-job-activity-hazard-effect/import', 'AdeN\Api\Modules\Config\JobActivityHazardEffect\Http\Controllers\ConfigJobActivityHazardEffectController@import');
    Route::post('config-job-activity-hazard-effect/upload', 'AdeN\Api\Modules\Config\JobActivityHazardEffect\Http\Controllers\ConfigJobActivityHazardEffectController@upload');
    Route::match(['get', 'post'], 'config-job-activity-hazard-effect', 'AdeN\Api\Modules\Config\JobActivityHazardEffect\Http\Controllers\ConfigJobActivityHazardEffectController@index');
	Route::match(['get', 'post'], 'config-job-activity-hazard-effect/download', 'AdeN\Api\Modules\Config\JobActivityHazardEffect\Http\Controllers\ConfigJobActivityHazardEffectController@download');