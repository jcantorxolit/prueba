<?php

	/**
     *Module: CustomerRoadSafetyItemDocument40595
     */
    Route::get('customer-road-safety-item-document-40595/get', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemDocument40595\Http\Controllers\CustomerRoadSafetyItemDocument40595Controller@show');
    Route::post('customer-road-safety-item-document-40595/save', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemDocument40595\Http\Controllers\CustomerRoadSafetyItemDocument40595Controller@store');
    Route::post('customer-road-safety-item-document-40595/delete', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemDocument40595\Http\Controllers\CustomerRoadSafetyItemDocument40595Controller@destroy');
    Route::post('customer-road-safety-item-document-40595/import', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemDocument40595\Http\Controllers\CustomerRoadSafetyItemDocument40595Controller@import');
    Route::post('customer-road-safety-item-document-40595/import-historical', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemDocument40595\Http\Controllers\CustomerRoadSafetyItemDocument40595Controller@importHistorical');
    Route::post('customer-road-safety-item-document-40595/upload', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemDocument40595\Http\Controllers\CustomerRoadSafetyItemDocument40595Controller@upload');
    Route::match(['get', 'post'], 'customer-road-safety-item-document-40595', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemDocument40595\Http\Controllers\CustomerRoadSafetyItemDocument40595Controller@index');
    Route::match(['get', 'post'], 'customer-road-safety-item-document-40595-available', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemDocument40595\Http\Controllers\CustomerRoadSafetyItemDocument40595Controller@indexAvailable');
    Route::match(['get', 'post'], 'customer-road-safety-item-document-40595-available-previous', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemDocument40595\Http\Controllers\CustomerRoadSafetyItemDocument40595Controller@indexAvailablePreviousPeriod');
	Route::match(['get', 'post'], 'customer-road-safety-item-document-40595/download', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemDocument40595\Http\Controllers\CustomerRoadSafetyItemDocument40595Controller@download');
