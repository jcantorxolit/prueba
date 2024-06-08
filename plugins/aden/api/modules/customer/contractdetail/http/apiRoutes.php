<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-contract-detail', 'AdeN\Api\Modules\Customer\ContractDetail\Http\Controllers\CustomerContractDetailController@show');
    Route::post('customer-contract-detail/save', 'AdeN\Api\Modules\Customer\ContractDetail\Http\Controllers\CustomerContractDetailController@store');
    Route::post('customer-contract-detail/update', 'AdeN\Api\Modules\Customer\ContractDetail\Http\Controllers\CustomerContractDetailController@update');
    Route::post('customer-contract-detail/delete', 'AdeN\Api\Modules\Customer\ContractDetail\Http\Controllers\CustomerContractDetailController@destroy');
    Route::post('customer-contract-detail/import', 'AdeN\Api\Modules\Customer\ContractDetail\Http\Controllers\CustomerContractDetailController@import');
    Route::post('customer-contract-detail/upload', 'AdeN\Api\Modules\Customer\ContractDetail\Http\Controllers\CustomerContractDetailController@upload');
    Route::match(['post'], 'customer-contract-detail', 'AdeN\Api\Modules\Customer\ContractDetail\Http\Controllers\CustomerContractDetailController@index');
    Route::match(['post'], 'customer-contract-detail-question', 'AdeN\Api\Modules\Customer\ContractDetail\Http\Controllers\CustomerContractDetailController@indexQuestion');
	Route::match(['get', 'post'], 'customer-contract-detail/download', 'AdeN\Api\Modules\Customer\ContractDetail\Http\Controllers\CustomerContractDetailController@download');