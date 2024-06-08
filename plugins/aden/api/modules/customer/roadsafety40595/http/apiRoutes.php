<?php

	/**
     *Module: CustomerRoadSafety40595
     */
    Route::get('customer-road-safety-40595/get', 'AdeN\Api\Modules\Customer\RoadSafety40595\Http\Controllers\CustomerRoadSafety40595Controller@show');
    Route::get('customer-road-safety-40595/find', 'AdeN\Api\Modules\Customer\RoadSafety40595\Http\Controllers\CustomerRoadSafety40595Controller@find');
    Route::post('customer-road-safety-40595/save', 'AdeN\Api\Modules\Customer\RoadSafety40595\Http\Controllers\CustomerRoadSafety40595Controller@store');
    Route::post('customer-road-safety-40595/delete', 'AdeN\Api\Modules\Customer\RoadSafety40595\Http\Controllers\CustomerRoadSafety40595Controller@destroy');
    Route::post('customer-road-safety-40595/import', 'AdeN\Api\Modules\Customer\RoadSafety40595\Http\Controllers\CustomerRoadSafety40595Controller@import');
    Route::post('customer-road-safety-40595/upload', 'AdeN\Api\Modules\Customer\RoadSafety40595\Http\Controllers\CustomerRoadSafety40595Controller@upload');
    Route::post('customer-road-safety-40595/migrate', 'AdeN\Api\Modules\Customer\RoadSafety40595\Http\Controllers\CustomerRoadSafety40595Controller@migrate');
    Route::match(['get', 'post'], 'customer-road-safety-40595', 'AdeN\Api\Modules\Customer\RoadSafety40595\Http\Controllers\CustomerRoadSafety40595Controller@index');
    Route::match(['get', 'post'], 'customer-road-safety-40595/export-pdf', 'AdeN\Api\Modules\Customer\RoadSafety40595\Http\Controllers\CustomerRoadSafety40595Controller@exportPdf');
    Route::match(['get', 'post'], 'customer-road-safety-40595-summary', 'AdeN\Api\Modules\Customer\RoadSafety40595\Http\Controllers\CustomerRoadSafety40595Controller@indexSummary');
    Route::match(['get', 'post'], 'customer-road-safety-40595-summary/export-excel', 'AdeN\Api\Modules\Customer\RoadSafety40595\Http\Controllers\CustomerRoadSafety40595Controller@exportSummary');
	Route::match(['get', 'post'], 'customer-road-safety-40595/download', 'AdeN\Api\Modules\Customer\RoadSafety40595\Http\Controllers\CustomerRoadSafety40595Controller@download');
