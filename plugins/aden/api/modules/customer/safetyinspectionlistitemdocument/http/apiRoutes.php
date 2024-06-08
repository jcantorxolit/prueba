<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-safety-inspection-list-item-document', 'AdeN\Api\Modules\Customer\SafetyInspectionListItemDocument\Http\Controllers\CustomerSafetyInspectionListItemDocumentController@show');
    Route::post('customer-safety-inspection-list-item-document/save', 'AdeN\Api\Modules\Customer\SafetyInspectionListItemDocument\Http\Controllers\CustomerSafetyInspectionListItemDocumentController@store');
    Route::post('customer-safety-inspection-list-item-document/update', 'AdeN\Api\Modules\Customer\SafetyInspectionListItemDocument\Http\Controllers\CustomerSafetyInspectionListItemDocumentController@update');
    Route::post('customer-safety-inspection-list-item-document/delete', 'AdeN\Api\Modules\Customer\SafetyInspectionListItemDocument\Http\Controllers\CustomerSafetyInspectionListItemDocumentController@destroy');
    Route::post('customer-safety-inspection-list-item-document/import', 'AdeN\Api\Modules\Customer\SafetyInspectionListItemDocument\Http\Controllers\CustomerSafetyInspectionListItemDocumentController@import');
    Route::post('customer-safety-inspection-list-item-document/upload', 'AdeN\Api\Modules\Customer\SafetyInspectionListItemDocument\Http\Controllers\CustomerSafetyInspectionListItemDocumentController@upload');
    Route::match(['post'], 'customer-safety-inspection-list-item-document', 'AdeN\Api\Modules\Customer\SafetyInspectionListItemDocument\Http\Controllers\CustomerSafetyInspectionListItemDocumentController@index');
	Route::match(['get', 'post'], 'customer-safety-inspection-list-item-document/download', 'AdeN\Api\Modules\Customer\SafetyInspectionListItemDocument\Http\Controllers\CustomerSafetyInspectionListItemDocumentController@download');