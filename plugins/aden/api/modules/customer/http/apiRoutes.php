<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@show');
    Route::get('customer/get-basic', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@showBasic');
    //Route::post('customer/save', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@store');
    //Route::post('customer/update', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@update');
    //Route::post('customer/delete', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@destroy');
    Route::post('customer/find', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@find');
    Route::post('customer/update-matrix', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@updateMatrix');
    Route::post('customer/import', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@import');
    Route::post('customer/upload', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@upload');
    Route::post('customer/sign-up', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@signUp');
    Route::match(['post'], 'customer', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@index');
    Route::match(['post'], 'customer-agent', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@index');
    Route::match(['post'], 'customer-contractor', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@index');
    Route::match(['post'], 'customer-economic-group', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@index');
    Route::match(['post'], 'customer-contractor-economic-group', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@index');
	//Route::match(['get', 'post'], 'customer/download', 'AdeN\Api\Modules\Customer\Http\Controllers\CustomerController@download');