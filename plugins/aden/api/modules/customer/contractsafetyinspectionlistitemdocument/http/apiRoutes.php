<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-contract-safety-inspection-list-item-document', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItemDocument\Http\Controllers\CustomerContractSafetyInspectionListItemDocumentController@show');
    Route::post('customer-contract-safety-inspection-list-item-document/save', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItemDocument\Http\Controllers\CustomerContractSafetyInspectionListItemDocumentController@store');
    Route::post('customer-contract-safety-inspection-list-item-document/update', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItemDocument\Http\Controllers\CustomerContractSafetyInspectionListItemDocumentController@update');
    Route::post('customer-contract-safety-inspection-list-item-document/delete', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItemDocument\Http\Controllers\CustomerContractSafetyInspectionListItemDocumentController@destroy');
    Route::post('customer-contract-safety-inspection-list-item-document/import', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItemDocument\Http\Controllers\CustomerContractSafetyInspectionListItemDocumentController@import');
    Route::post('customer-contract-safety-inspection-list-item-document/upload', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItemDocument\Http\Controllers\CustomerContractSafetyInspectionListItemDocumentController@upload');
    Route::match(['post'], 'customer-contract-safety-inspection-list-item-document', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItemDocument\Http\Controllers\CustomerContractSafetyInspectionListItemDocumentController@index');
	Route::match(['get', 'post'], 'customer-contract-safety-inspection-list-item-document/download', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListItemDocument\Http\Controllers\CustomerContractSafetyInspectionListItemDocumentController@download');