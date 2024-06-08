<?php
    
	/**
     *Module: CustomerParameter
     */
    // Route::get('customer-parameter/get', 'AdeN\Api\Modules\Customer\Parameter\Http\Controllers\CustomerParameterController@show');
    // Route::post('customer-parameter/save', 'AdeN\Api\Modules\Customer\Parameter\Http\Controllers\CustomerParameterController@store');
    Route::post('customer-parameter/destroy', 'AdeN\Api\Modules\Customer\Parameter\Http\Controllers\CustomerParameterController@destroy');
    // Route::post('customer-parameter/import', 'AdeN\Api\Modules\Customer\Parameter\Http\Controllers\CustomerParameterController@import');
    // Route::post('customer-parameter/upload', 'AdeN\Api\Modules\Customer\Parameter\Http\Controllers\CustomerParameterController@upload');
    // Route::match(['get', 'post'], 'customer-parameter', 'AdeN\Api\Modules\Customer\Parameter\Http\Controllers\CustomerParameterController@index');
	// Route::match(['get', 'post'], 'customer-parameter/download', 'AdeN\Api\Modules\Customer\Parameter\Http\Controllers\CustomerParameterController@download');