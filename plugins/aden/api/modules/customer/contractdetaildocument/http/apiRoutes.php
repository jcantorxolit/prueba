<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-contract-detail-document', 'AdeN\Api\Modules\Customer\ContractDetailDocument\Http\Controllers\CustomerContractDetailDocumentController@show');
    Route::post('customer-contract-detail-document/save', 'AdeN\Api\Modules\Customer\ContractDetailDocument\Http\Controllers\CustomerContractDetailDocumentController@store');
    Route::post('customer-contract-detail-document/update', 'AdeN\Api\Modules\Customer\ContractDetailDocument\Http\Controllers\CustomerContractDetailDocumentController@update');
    Route::post('customer-contract-detail-document/delete', 'AdeN\Api\Modules\Customer\ContractDetailDocument\Http\Controllers\CustomerContractDetailDocumentController@destroy');
    Route::post('customer-contract-detail-document/import', 'AdeN\Api\Modules\Customer\ContractDetailDocument\Http\Controllers\CustomerContractDetailDocumentController@import');
    Route::post('customer-contract-detail-document/upload', 'AdeN\Api\Modules\Customer\ContractDetailDocument\Http\Controllers\CustomerContractDetailDocumentController@upload');
    Route::match(['post'], 'customer-contract-detail-document', 'AdeN\Api\Modules\Customer\ContractDetailDocument\Http\Controllers\CustomerContractDetailDocumentController@index');
	Route::match(['get', 'post'], 'customer-contract-detail-document/download', 'AdeN\Api\Modules\Customer\ContractDetailDocument\Http\Controllers\CustomerContractDetailDocumentController@download');