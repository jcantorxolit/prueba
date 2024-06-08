<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-contract-detail-comment', 'AdeN\Api\Modules\Customer\ContractDetailComment\Http\Controllers\CustomerContractDetailCommentController@show');
    Route::post('customer-contract-detail-comment/save', 'AdeN\Api\Modules\Customer\ContractDetailComment\Http\Controllers\CustomerContractDetailCommentController@store');
    Route::post('customer-contract-detail-comment/update', 'AdeN\Api\Modules\Customer\ContractDetailComment\Http\Controllers\CustomerContractDetailCommentController@update');
    Route::post('customer-contract-detail-comment/delete', 'AdeN\Api\Modules\Customer\ContractDetailComment\Http\Controllers\CustomerContractDetailCommentController@destroy');
    Route::post('customer-contract-detail-comment/import', 'AdeN\Api\Modules\Customer\ContractDetailComment\Http\Controllers\CustomerContractDetailCommentController@import');
    Route::post('customer-contract-detail-comment/upload', 'AdeN\Api\Modules\Customer\ContractDetailComment\Http\Controllers\CustomerContractDetailCommentController@upload');
    Route::match(['post'], 'customer-contract-detail-comment', 'AdeN\Api\Modules\Customer\ContractDetailComment\Http\Controllers\CustomerContractDetailCommentController@index');
	Route::match(['get', 'post'], 'customer-contract-detail-comment/download', 'AdeN\Api\Modules\Customer\ContractDetailComment\Http\Controllers\CustomerContractDetailCommentController@download');