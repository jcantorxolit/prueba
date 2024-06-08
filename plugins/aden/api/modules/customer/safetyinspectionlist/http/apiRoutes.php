<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-safety-inspection-list', 'AdeN\Api\Modules\Customer\SafetyInspectionList\Http\Controllers\CustomerSafetyInspectionListController@show');
    Route::post('customer-safety-inspection-list/save', 'AdeN\Api\Modules\Customer\SafetyInspectionList\Http\Controllers\CustomerSafetyInspectionListController@store');
    Route::post('customer-safety-inspection-list/update', 'AdeN\Api\Modules\Customer\SafetyInspectionList\Http\Controllers\CustomerSafetyInspectionListController@update');
    Route::post('customer-safety-inspection-list/delete', 'AdeN\Api\Modules\Customer\SafetyInspectionList\Http\Controllers\CustomerSafetyInspectionListController@destroy');
    Route::post('customer-safety-inspection-list/import', 'AdeN\Api\Modules\Customer\SafetyInspectionList\Http\Controllers\CustomerSafetyInspectionListController@import');
    Route::post('customer-safety-inspection-list/upload', 'AdeN\Api\Modules\Customer\SafetyInspectionList\Http\Controllers\CustomerSafetyInspectionListController@upload');
    Route::match(['post'], 'customer-safety-inspection-list', 'AdeN\Api\Modules\Customer\SafetyInspectionList\Http\Controllers\CustomerSafetyInspectionListController@index');
	Route::match(['get', 'post'], 'customer-safety-inspection-list/download', 'AdeN\Api\Modules\Customer\SafetyInspectionList\Http\Controllers\CustomerSafetyInspectionListController@download');