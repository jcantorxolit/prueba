<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-contract-safety-inspection-header-field', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionHeaderField\Http\Controllers\CustomerContractSafetyInspectionHeaderFieldController@show');
    Route::post('customer-contract-safety-inspection-header-field/save', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionHeaderField\Http\Controllers\CustomerContractSafetyInspectionHeaderFieldController@store');
    Route::post('customer-contract-safety-inspection-header-field/batch', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionHeaderField\Http\Controllers\CustomerContractSafetyInspectionHeaderFieldController@batch');
    Route::post('customer-contract-safety-inspection-header-field/update', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionHeaderField\Http\Controllers\CustomerContractSafetyInspectionHeaderFieldController@update');
    Route::post('customer-contract-safety-inspection-header-field/delete', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionHeaderField\Http\Controllers\CustomerContractSafetyInspectionHeaderFieldController@destroy');
    Route::post('customer-contract-safety-inspection-header-field/import', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionHeaderField\Http\Controllers\CustomerContractSafetyInspectionHeaderFieldController@import');
    Route::post('customer-contract-safety-inspection-header-field/upload', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionHeaderField\Http\Controllers\CustomerContractSafetyInspectionHeaderFieldController@upload');
    Route::match(['post'], 'customer-contract-safety-inspection-header-field', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionHeaderField\Http\Controllers\CustomerContractSafetyInspectionHeaderFieldController@index');
	Route::match(['get', 'post'], 'customer-contract-safety-inspection-header-field/download', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionHeaderField\Http\Controllers\CustomerContractSafetyInspectionHeaderFieldController@download');