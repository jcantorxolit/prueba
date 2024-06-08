<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-contract-safety-inspection-list-observation', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListObservation\Http\Controllers\CustomerContractSafetyInspectionListObservationController@show');
    Route::post('customer-contract-safety-inspection-list-observation/save', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListObservation\Http\Controllers\CustomerContractSafetyInspectionListObservationController@store');
    Route::post('customer-contract-safety-inspection-list-observation/update', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListObservation\Http\Controllers\CustomerContractSafetyInspectionListObservationController@update');
    Route::post('customer-contract-safety-inspection-list-observation/delete', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListObservation\Http\Controllers\CustomerContractSafetyInspectionListObservationController@destroy');
    Route::post('customer-contract-safety-inspection-list-observation/import', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListObservation\Http\Controllers\CustomerContractSafetyInspectionListObservationController@import');
    Route::post('customer-contract-safety-inspection-list-observation/upload', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListObservation\Http\Controllers\CustomerContractSafetyInspectionListObservationController@upload');
    Route::match(['post'], 'customer-contract-safety-inspection-list-observation', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListObservation\Http\Controllers\CustomerContractSafetyInspectionListObservationController@index');
	Route::match(['get', 'post'], 'customer-contract-safety-inspection-list-observation/download', 'AdeN\Api\Modules\Customer\ContractSafetyInspectionListObservation\Http\Controllers\CustomerContractSafetyInspectionListObservationController@download');