<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-contract-safety-inspection', 'AdeN\Api\Modules\Customer\ContractSafetyInspection\Http\Controllers\CustomerContractSafetyInspectionController@show');
    Route::post('customer-contract-safety-inspection/save', 'AdeN\Api\Modules\Customer\ContractSafetyInspection\Http\Controllers\CustomerContractSafetyInspectionController@store');
    Route::post('customer-contract-safety-inspection/update', 'AdeN\Api\Modules\Customer\ContractSafetyInspection\Http\Controllers\CustomerContractSafetyInspectionController@update');
    Route::post('customer-contract-safety-inspection/delete', 'AdeN\Api\Modules\Customer\ContractSafetyInspection\Http\Controllers\CustomerContractSafetyInspectionController@destroy');
    Route::post('customer-contract-safety-inspection/import', 'AdeN\Api\Modules\Customer\ContractSafetyInspection\Http\Controllers\CustomerContractSafetyInspectionController@import');
    Route::post('customer-contract-safety-inspection/upload', 'AdeN\Api\Modules\Customer\ContractSafetyInspection\Http\Controllers\CustomerContractSafetyInspectionController@upload');
    Route::match(['post'], 'customer-contract-safety-inspection', 'AdeN\Api\Modules\Customer\ContractSafetyInspection\Http\Controllers\CustomerContractSafetyInspectionController@index');
	Route::match(['get', 'post'], 'customer-contract-safety-inspection/download', 'AdeN\Api\Modules\Customer\ContractSafetyInspection\Http\Controllers\CustomerContractSafetyInspectionController@download');