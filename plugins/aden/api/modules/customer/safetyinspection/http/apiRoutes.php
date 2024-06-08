<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-safety-inspection', 'AdeN\Api\Modules\Customer\SafetyInspection\Http\Controllers\CustomerSafetyInspectionController@show');
    Route::post('customer-safety-inspection/save', 'AdeN\Api\Modules\Customer\SafetyInspection\Http\Controllers\CustomerSafetyInspectionController@store');
    Route::post('customer-safety-inspection/update', 'AdeN\Api\Modules\Customer\SafetyInspection\Http\Controllers\CustomerSafetyInspectionController@update');
    Route::post('customer-safety-inspection/delete', 'AdeN\Api\Modules\Customer\SafetyInspection\Http\Controllers\CustomerSafetyInspectionController@destroy');
    Route::post('customer-safety-inspection/import', 'AdeN\Api\Modules\Customer\SafetyInspection\Http\Controllers\CustomerSafetyInspectionController@import');
    Route::post('customer-safety-inspection/upload', 'AdeN\Api\Modules\Customer\SafetyInspection\Http\Controllers\CustomerSafetyInspectionController@upload');
    Route::match(['post'], 'customer-safety-inspection', 'AdeN\Api\Modules\Customer\SafetyInspection\Http\Controllers\CustomerSafetyInspectionController@index');
	Route::match(['get', 'post'], 'customer-safety-inspection/download', 'AdeN\Api\Modules\Customer\SafetyInspection\Http\Controllers\CustomerSafetyInspectionController@download');