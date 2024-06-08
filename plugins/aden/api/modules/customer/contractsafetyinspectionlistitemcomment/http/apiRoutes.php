<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-contract-safety-inspection-list-item-comment', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItemComment\Http\Controllers\CustomerContractSafetyInspectionListItemCommentController@show');
    Route::post('customer-contract-safety-inspection-list-item-comment/save', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItemComment\Http\Controllers\CustomerContractSafetyInspectionListItemCommentController@store');
    Route::post('customer-contract-safety-inspection-list-item-comment/update', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItemComment\Http\Controllers\CustomerContractSafetyInspectionListItemCommentController@update');
    Route::post('customer-contract-safety-inspection-list-item-comment/delete', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItemComment\Http\Controllers\CustomerContractSafetyInspectionListItemCommentController@destroy');
    Route::post('customer-contract-safety-inspection-list-item-comment/import', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItemComment\Http\Controllers\CustomerContractSafetyInspectionListItemCommentController@import');
    Route::post('customer-contract-safety-inspection-list-item-comment/upload', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItemComment\Http\Controllers\CustomerContractSafetyInspectionListItemCommentController@upload');
    Route::match(['post'], 'customer-contract-safety-inspection-list-item-comment', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItemComment\Http\Controllers\CustomerContractSafetyInspectionListItemCommentController@index');
	Route::match(['get', 'post'], 'customer-contract-safety-inspection-list-item-comment/download', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItemComment\Http\Controllers\CustomerContractSafetyInspectionListItemCommentController@download');