<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-safety-inspection-list-item-comment', 'AdeN\Api\Modules\Customer\SafetyInspectionListItemComment\Http\Controllers\CustomerSafetyInspectionListItemCommentController@show');
    Route::post('customer-safety-inspection-list-item-comment/save', 'AdeN\Api\Modules\Customer\SafetyInspectionListItemComment\Http\Controllers\CustomerSafetyInspectionListItemCommentController@store');
    Route::post('customer-safety-inspection-list-item-comment/update', 'AdeN\Api\Modules\Customer\SafetyInspectionListItemComment\Http\Controllers\CustomerSafetyInspectionListItemCommentController@update');
    Route::post('customer-safety-inspection-list-item-comment/delete', 'AdeN\Api\Modules\Customer\SafetyInspectionListItemComment\Http\Controllers\CustomerSafetyInspectionListItemCommentController@destroy');
    Route::post('customer-safety-inspection-list-item-comment/import', 'AdeN\Api\Modules\Customer\SafetyInspectionListItemComment\Http\Controllers\CustomerSafetyInspectionListItemCommentController@import');
    Route::post('customer-safety-inspection-list-item-comment/upload', 'AdeN\Api\Modules\Customer\SafetyInspectionListItemComment\Http\Controllers\CustomerSafetyInspectionListItemCommentController@upload');
    Route::match(['post'], 'customer-safety-inspection-list-item-comment', 'AdeN\Api\Modules\Customer\SafetyInspectionListItemComment\Http\Controllers\CustomerSafetyInspectionListItemCommentController@index');
	Route::match(['get', 'post'], 'customer-safety-inspection-list-item-comment/download', 'AdeN\Api\Modules\Customer\SafetyInspectionListItemComment\Http\Controllers\CustomerSafetyInspectionListItemCommentController@download');