<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-road-safety-item', 'AdeN\Api\Modules\Customer\RoadSafetyItem\Http\Controllers\CustomerRoadSafetyItemController@show');
    Route::post('customer-road-safety-item/save', 'AdeN\Api\Modules\Customer\RoadSafetyItem\Http\Controllers\CustomerRoadSafetyItemController@store');
    Route::post('customer-road-safety-item/update', 'AdeN\Api\Modules\Customer\RoadSafetyItem\Http\Controllers\CustomerRoadSafetyItemController@update');
    Route::post('customer-road-safety-item/delete', 'AdeN\Api\Modules\Customer\RoadSafetyItem\Http\Controllers\CustomerRoadSafetyItemController@destroy');
    Route::post('customer-road-safety-item/import', 'AdeN\Api\Modules\Customer\RoadSafetyItem\Http\Controllers\CustomerRoadSafetyItemController@import');
    Route::post('customer-road-safety-item/upload', 'AdeN\Api\Modules\Customer\RoadSafetyItem\Http\Controllers\CustomerRoadSafetyItemController@upload');
    Route::match(['post'], 'customer-road-safety-item', 'AdeN\Api\Modules\Customer\RoadSafetyItem\Http\Controllers\CustomerRoadSafetyItemController@index');
    Route::match(['post'], 'customer-road-safety-item-question', 'AdeN\Api\Modules\Customer\RoadSafetyItem\Http\Controllers\CustomerRoadSafetyItemController@indexQuestion');
	Route::match(['get', 'post'], 'customer-road-safety-item/download', 'AdeN\Api\Modules\Customer\RoadSafetyItem\Http\Controllers\CustomerRoadSafetyItemController@download');