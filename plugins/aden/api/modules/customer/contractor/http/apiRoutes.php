<?php
    
	/**
     *Module: CustomerContractor
     */
    Route::get('customer-contractor/get', 'AdeN\Api\Modules\Customer\Contractor\Http\Controllers\CustomerContractorController@show');
    Route::post('customer-contractor/save', 'AdeN\Api\Modules\Customer\Contractor\Http\Controllers\CustomerContractorController@store');
    Route::post('customer-contractor/delete', 'AdeN\Api\Modules\Customer\Contractor\Http\Controllers\CustomerContractorController@destroy');
    Route::post('customer-contractor/import', 'AdeN\Api\Modules\Customer\Contractor\Http\Controllers\CustomerContractorController@import');
    Route::post('customer-contractor/upload', 'AdeN\Api\Modules\Customer\Contractor\Http\Controllers\CustomerContractorController@upload');
    Route::match(['get', 'post'], 'customer-contractor-index', 'AdeN\Api\Modules\Customer\Contractor\Http\Controllers\CustomerContractorController@index');
	Route::match(['get', 'post'], 'customer-contractor/download', 'AdeN\Api\Modules\Customer\Contractor\Http\Controllers\CustomerContractorController@download');

	Route::post('customer-contractor/customer-relationships', 'AdeN\Api\Modules\Customer\Contractor\Http\Controllers\CustomerContractorController@getCustomerRelationships');