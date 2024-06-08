<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-safety-inspection-header-field', 'AdeN\Api\Modules\Customer\SafetyInspectionHeaderField\Http\Controllers\CustomerSafetyInspectionHeaderFieldController@show');
    Route::post('customer-safety-inspection-header-field/save', 'AdeN\Api\Modules\Customer\SafetyInspectionHeaderField\Http\Controllers\CustomerSafetyInspectionHeaderFieldController@store');
    Route::post('customer-safety-inspection-header-field/batch', 'AdeN\Api\Modules\Customer\SafetyInspectionHeaderField\Http\Controllers\CustomerSafetyInspectionHeaderFieldController@batch');
    Route::post('customer-safety-inspection-header-field/update', 'AdeN\Api\Modules\Customer\SafetyInspectionHeaderField\Http\Controllers\CustomerSafetyInspectionHeaderFieldController@update');
    Route::post('customer-safety-inspection-header-field/delete', 'AdeN\Api\Modules\Customer\SafetyInspectionHeaderField\Http\Controllers\CustomerSafetyInspectionHeaderFieldController@destroy');
    Route::post('customer-safety-inspection-header-field/import', 'AdeN\Api\Modules\Customer\SafetyInspectionHeaderField\Http\Controllers\CustomerSafetyInspectionHeaderFieldController@import');
    Route::post('customer-safety-inspection-header-field/upload', 'AdeN\Api\Modules\Customer\SafetyInspectionHeaderField\Http\Controllers\CustomerSafetyInspectionHeaderFieldController@upload');
    Route::match(['post'], 'customer-safety-inspection-header-field', 'AdeN\Api\Modules\Customer\SafetyInspectionHeaderField\Http\Controllers\CustomerSafetyInspectionHeaderFieldController@index');
	Route::match(['get', 'post'], 'customer-safety-inspection-header-field/download', 'AdeN\Api\Modules\Customer\SafetyInspectionHeaderField\Http\Controllers\CustomerSafetyInspectionHeaderFieldController@download');