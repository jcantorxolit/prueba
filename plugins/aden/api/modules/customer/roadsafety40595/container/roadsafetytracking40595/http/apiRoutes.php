<?php

	/**
     *Module: CustomerRoadSafetyTracking40595
     */
    Route::get('customer-road-safety-tracking-40595/get', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyTracking40595\Http\Controllers\CustomerRoadSafetyTracking40595Controller@show');
    Route::post('customer-road-safety-tracking-40595/save', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyTracking40595\Http\Controllers\CustomerRoadSafetyTracking40595Controller@store');
    Route::post('customer-road-safety-tracking-40595/delete', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyTracking40595\Http\Controllers\CustomerRoadSafetyTracking40595Controller@destroy');
    Route::post('customer-road-safety-tracking-40595/import', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyTracking40595\Http\Controllers\CustomerRoadSafetyTracking40595Controller@import');
    Route::post('customer-road-safety-tracking-40595/upload', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyTracking40595\Http\Controllers\CustomerRoadSafetyTracking40595Controller@upload');
    Route::match(['get', 'post'], 'customer-road-safety-tracking-40595', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyTracking40595\Http\Controllers\CustomerRoadSafetyTracking40595Controller@index');
    Route::match(['get', 'post'], 'customer-road-safety-tracking-40595-summary-cycle', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyTracking40595\Http\Controllers\CustomerRoadSafetyTracking40595Controller@indexSummaryCycle');
    Route::match(['get', 'post'], 'customer-road-safety-tracking-40595-summary-cycle-detail', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyTracking40595\Http\Controllers\CustomerRoadSafetyTracking40595Controller@indexSummaryCycleDetail');
    Route::match(['get', 'post'], 'customer-road-safety-tracking-40595-summary-indicator', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyTracking40595\Http\Controllers\CustomerRoadSafetyTracking40595Controller@indexSummaryIndicator');
    Route::match(['get', 'post'], 'customer-road-safety-tracking-40595-summary-cycle/export-excel', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyTracking40595\Http\Controllers\CustomerRoadSafetyTracking40595Controller@exportSummaryCycle');
    Route::match(['get', 'post'], 'customer-road-safety-tracking-40595-summary-indicator/export-excel', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyTracking40595\Http\Controllers\CustomerRoadSafetyTracking40595Controller@exportSummaryIndicator');
	Route::match(['get', 'post'], 'customer-road-safety-tracking-40595/download', 'AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyTracking40595\Http\Controllers\CustomerRoadSafetyTracking40595Controller@download');
