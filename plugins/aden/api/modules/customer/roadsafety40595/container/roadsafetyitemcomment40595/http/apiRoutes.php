<?php

	/**
     *Module: CustomerRoadSafetyItemComment40595
     */
    Route::get('customer-road-safety-item-comment-40595/get', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemComment40595\Http\Controllers\CustomerRoadSafetyItemComment40595Controller@show');
    Route::post('customer-road-safety-item-comment-40595/save', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemComment40595\Http\Controllers\CustomerRoadSafetyItemComment40595Controller@store');
    Route::post('customer-road-safety-item-comment-40595/delete', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemComment40595\Http\Controllers\CustomerRoadSafetyItemComment40595Controller@destroy');
    Route::post('customer-road-safety-item-comment-40595/import', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemComment40595\Http\Controllers\CustomerRoadSafetyItemComment40595Controller@import');
    Route::post('customer-road-safety-item-comment-40595/upload', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemComment40595\Http\Controllers\CustomerRoadSafetyItemComment40595Controller@upload');
    Route::match(['get', 'post'], 'customer-road-safety-item-comment-40595', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemComment40595\Http\Controllers\CustomerRoadSafetyItemComment40595Controller@index');
	Route::match(['get', 'post'], 'customer-road-safety-item-comment-40595/download', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemComment40595\Http\Controllers\CustomerRoadSafetyItemComment40595Controller@download');
