<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-safety-inspection-list-item', 'AdeN\Api\Modules\Customer\SafetyInspectionListItem\Http\Controllers\CustomerSafetyInspectionListItemController@show');
    Route::post('customer-safety-inspection-list-item/save', 'AdeN\Api\Modules\Customer\SafetyInspectionListItem\Http\Controllers\CustomerSafetyInspectionListItemController@store');
    Route::post('customer-safety-inspection-list-item/batch', 'AdeN\Api\Modules\Customer\SafetyInspectionListItem\Http\Controllers\CustomerSafetyInspectionListItemController@batch');
    Route::post('customer-safety-inspection-list-item/update', 'AdeN\Api\Modules\Customer\SafetyInspectionListItem\Http\Controllers\CustomerSafetyInspectionListItemController@update');
    Route::post('customer-safety-inspection-list-item/delete', 'AdeN\Api\Modules\Customer\SafetyInspectionListItem\Http\Controllers\CustomerSafetyInspectionListItemController@destroy');
    Route::post('customer-safety-inspection-list-item/import', 'AdeN\Api\Modules\Customer\SafetyInspectionListItem\Http\Controllers\CustomerSafetyInspectionListItemController@import');
    Route::post('customer-safety-inspection-list-item/upload', 'AdeN\Api\Modules\Customer\SafetyInspectionListItem\Http\Controllers\CustomerSafetyInspectionListItemController@upload');
    Route::match(['post'], 'customer-safety-inspection-list-item', 'AdeN\Api\Modules\Customer\SafetyInspectionListItem\Http\Controllers\CustomerSafetyInspectionListItemController@index');
    Route::match(['post'], 'customer-safety-inspection-list-item-question', 'AdeN\Api\Modules\Customer\SafetyInspectionListItem\Http\Controllers\CustomerSafetyInspectionListItemController@indexQuestion');
    Route::match(['post'], 'customer-safety-inspection-list-item-dangerousness', 'AdeN\Api\Modules\Customer\SafetyInspectionListItem\Http\Controllers\CustomerSafetyInspectionListItemController@indexDangerousness');
    Route::match(['post'], 'customer-safety-inspection-list-item-action', 'AdeN\Api\Modules\Customer\SafetyInspectionListItem\Http\Controllers\CustomerSafetyInspectionListItemController@indexAction');
	Route::match(['get', 'post'], 'customer-safety-inspection-list-item/download', 'AdeN\Api\Modules\Customer\SafetyInspectionListItem\Http\Controllers\CustomerSafetyInspectionListItemController@download');