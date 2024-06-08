<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-contract-safety-inspection-list-item', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItem\Http\Controllers\CustomerContractSafetyInspectionListItemController@show');
    Route::post('customer-contract-safety-inspection-list-item/save', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItem\Http\Controllers\CustomerContractSafetyInspectionListItemController@store');
    Route::post('customer-contract-safety-inspection-list-item/batch', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItem\Http\Controllers\CustomerContractSafetyInspectionListItemController@batch');
    Route::post('customer-contract-safety-inspection-list-item/update', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItem\Http\Controllers\CustomerContractSafetyInspectionListItemController@update');
    Route::post('customer-contract-safety-inspection-list-item/delete', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItem\Http\Controllers\CustomerContractSafetyInspectionListItemController@destroy');
    Route::post('customer-contract-safety-inspection-list-item/import', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItem\Http\Controllers\CustomerContractSafetyInspectionListItemController@import');
    Route::post('customer-contract-safety-inspection-list-item/upload', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItem\Http\Controllers\CustomerContractSafetyInspectionListItemController@upload');
    Route::match(['post'], 'customer-contract-safety-inspection-list-item', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItem\Http\Controllers\CustomerContractSafetyInspectionListItemController@index');
    Route::match(['post'], 'customer-contract-safety-inspection-list-item-question', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItem\Http\Controllers\CustomerContractSafetyInspectionListItemController@indexQuestion');
    Route::match(['post'], 'customer-contract-safety-inspection-list-item-dangerousness', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItem\Http\Controllers\CustomerContractSafetyInspectionListItemController@indexDangerousness');
    Route::match(['post'], 'customer-contract-safety-inspection-list-item-action', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItem\Http\Controllers\CustomerContractSafetyInspectionListItemController@indexAction');
	Route::match(['get', 'post'], 'customer-contract-safety-inspection-list-item/download', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItem\Http\Controllers\CustomerContractSafetyInspectionListItemController@download');