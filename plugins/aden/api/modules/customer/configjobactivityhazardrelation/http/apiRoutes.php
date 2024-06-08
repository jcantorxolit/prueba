<?php
    
	/**
     *Module: CustomerConfigJobActivityHazardRelation
     */
    Route::get('customer-config-job-activity-hazard-relation/get', 'AdeN\Api\Modules\Customer\ConfigJobActivityHazardRelation\Http\Controllers\CustomerConfigJobActivityHazardRelationController@show');
    Route::post('customer-config-job-activity-hazard-relation/save', 'AdeN\Api\Modules\Customer\ConfigJobActivityHazardRelation\Http\Controllers\CustomerConfigJobActivityHazardRelationController@store');
    Route::post('customer-config-job-activity-hazard-relation/batch', 'AdeN\Api\Modules\Customer\ConfigJobActivityHazardRelation\Http\Controllers\CustomerConfigJobActivityHazardRelationController@batch');
    Route::post('customer-config-job-activity-hazard-relation/delete', 'AdeN\Api\Modules\Customer\ConfigJobActivityHazardRelation\Http\Controllers\CustomerConfigJobActivityHazardRelationController@destroy');
    Route::post('customer-config-job-activity-hazard-relation/import', 'AdeN\Api\Modules\Customer\ConfigJobActivityHazardRelation\Http\Controllers\CustomerConfigJobActivityHazardRelationController@import');
    Route::post('customer-config-job-activity-hazard-relation/upload', 'AdeN\Api\Modules\Customer\ConfigJobActivityHazardRelation\Http\Controllers\CustomerConfigJobActivityHazardRelationController@upload');
    Route::match(['get', 'post'], 'customer-config-job-activity-hazard-relation', 'AdeN\Api\Modules\Customer\ConfigJobActivityHazardRelation\Http\Controllers\CustomerConfigJobActivityHazardRelationController@index');
    Route::match(['get', 'post'], 'customer-config-job-activity-hazard-available', 'AdeN\Api\Modules\Customer\ConfigJobActivityHazardRelation\Http\Controllers\CustomerConfigJobActivityHazardRelationController@indexAvailable');
	Route::match(['get', 'post'], 'customer-config-job-activity-hazard-relation/download', 'AdeN\Api\Modules\Customer\ConfigJobActivityHazardRelation\Http\Controllers\CustomerConfigJobActivityHazardRelationController@download');