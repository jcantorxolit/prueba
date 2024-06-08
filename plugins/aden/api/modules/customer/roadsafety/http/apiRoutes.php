<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-road-safety', 'AdeN\Api\Modules\Customer\RoadSafety\Http\Controllers\CustomerRoadSafetyController@show');
    Route::post('customer-road-safety/save', 'AdeN\Api\Modules\Customer\RoadSafety\Http\Controllers\CustomerRoadSafetyController@store');
    Route::post('customer-road-safety/update', 'AdeN\Api\Modules\Customer\RoadSafety\Http\Controllers\CustomerRoadSafetyController@update');
    Route::post('customer-road-safety/delete', 'AdeN\Api\Modules\Customer\RoadSafety\Http\Controllers\CustomerRoadSafetyController@destroy');
    Route::post('customer-road-safety/import', 'AdeN\Api\Modules\Customer\RoadSafety\Http\Controllers\CustomerRoadSafetyController@import');
    Route::post('customer-road-safety/upload', 'AdeN\Api\Modules\Customer\RoadSafety\Http\Controllers\CustomerRoadSafetyController@upload');
    Route::match(['post'], 'customer-road-safety', 'AdeN\Api\Modules\Customer\RoadSafety\Http\Controllers\CustomerRoadSafetyController@index');
    Route::match(['post'], 'customer-road-safety-summary', 'AdeN\Api\Modules\Customer\RoadSafety\Http\Controllers\CustomerRoadSafetyController@indexSummary');
	Route::match(['get', 'post'], 'customer-road-safety/download', 'AdeN\Api\Modules\Customer\RoadSafety\Http\Controllers\CustomerRoadSafetyController@download');