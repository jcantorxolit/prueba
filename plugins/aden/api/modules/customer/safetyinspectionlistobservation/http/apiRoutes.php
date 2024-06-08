<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-safety-inspection-list-observation', 'AdeN\Api\Modules\Customer\SafetyInspectionListObservation\Http\Controllers\CustomerSafetyInspectionListObservationController@show');
    Route::post('customer-safety-inspection-list-observation/save', 'AdeN\Api\Modules\Customer\SafetyInspectionListObservation\Http\Controllers\CustomerSafetyInspectionListObservationController@store');
    Route::post('customer-safety-inspection-list-observation/update', 'AdeN\Api\Modules\Customer\SafetyInspectionListObservation\Http\Controllers\CustomerSafetyInspectionListObservationController@update');
    Route::post('customer-safety-inspection-list-observation/delete', 'AdeN\Api\Modules\Customer\SafetyInspectionListObservation\Http\Controllers\CustomerSafetyInspectionListObservationController@destroy');
    Route::post('customer-safety-inspection-list-observation/import', 'AdeN\Api\Modules\Customer\SafetyInspectionListObservation\Http\Controllers\CustomerSafetyInspectionListObservationController@import');
    Route::post('customer-safety-inspection-list-observation/upload', 'AdeN\Api\Modules\Customer\SafetyInspectionListObservation\Http\Controllers\CustomerSafetyInspectionListObservationController@upload');
    Route::match(['post'], 'customer-safety-inspection-list-observation', 'AdeN\Api\Modules\Customer\SafetyInspectionListObservation\Http\Controllers\CustomerSafetyInspectionListObservationController@index');
	Route::match(['get', 'post'], 'customer-safety-inspection-list-observation/download', 'AdeN\Api\Modules\Customer\SafetyInspectionListObservation\Http\Controllers\CustomerSafetyInspectionListObservationController@download');