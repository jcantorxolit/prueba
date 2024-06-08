<?php

	/**
     *Module: CustomerRoadSafetyItemVerification40595
     */
    Route::get('customer-road-safety-item-verification-40595/get', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemVerification40595\Http\Controllers\CustomerRoadSafetyItemVerification40595Controller@show');
    Route::post('customer-road-safety-item-verification-40595/save', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemVerification40595\Http\Controllers\CustomerRoadSafetyItemVerification40595Controller@store');
    Route::post('customer-road-safety-item-verification-40595/delete', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemVerification40595\Http\Controllers\CustomerRoadSafetyItemVerification40595Controller@destroy');
    Route::post('customer-road-safety-item-verification-40595/import', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemVerification40595\Http\Controllers\CustomerRoadSafetyItemVerification40595Controller@import');
    Route::post('customer-road-safety-item-verification-40595/upload', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemVerification40595\Http\Controllers\CustomerRoadSafetyItemVerification40595Controller@upload');
    Route::match(['get', 'post'], 'customer-road-safety-item-verification-40595', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemVerification40595\Http\Controllers\CustomerRoadSafetyItemVerification40595Controller@index');
	Route::match(['get', 'post'], 'customer-road-safety-item-verification-40595/download', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemVerification40595\Http\Controllers\CustomerRoadSafetyItemVerification40595Controller@download');
