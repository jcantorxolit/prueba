<?php
    
	/**
     *Module: CustomerUser
     */
    Route::get('customer-user/get', 'AdeN\Api\Modules\Customer\User\Http\Controllers\CustomerUserController@show');
    Route::post('customer-user/save', 'AdeN\Api\Modules\Customer\User\Http\Controllers\CustomerUserController@store');
    Route::post('customer-user/relate-customer', 'AdeN\Api\Modules\Customer\User\Http\Controllers\CustomerUserController@relateCustomer');
    Route::post('customer-user/toggle-active', 'AdeN\Api\Modules\Customer\User\Http\Controllers\CustomerUserController@toggleActive');
    Route::post('customer-user/delete', 'AdeN\Api\Modules\Customer\User\Http\Controllers\CustomerUserController@destroy');    
    Route::post('customer-user/import', 'AdeN\Api\Modules\Customer\User\Http\Controllers\CustomerUserController@import');
    Route::post('customer-user/upload', 'AdeN\Api\Modules\Customer\User\Http\Controllers\CustomerUserController@upload');
    Route::match(['get', 'post'], 'customer-user', 'AdeN\Api\Modules\Customer\User\Http\Controllers\CustomerUserController@index');    
    Route::match(['get', 'post'], 'customer-user-contractor-economic-group', 'AdeN\Api\Modules\Customer\User\Http\Controllers\CustomerUserController@index');
    Route::match(['get', 'post'], 'customer-user-customer-available', 'AdeN\Api\Modules\Customer\User\Http\Controllers\CustomerUserController@indexCustomerAvailable');
    Route::match(['get', 'post'], 'customer-user-customer-related', 'AdeN\Api\Modules\Customer\User\Http\Controllers\CustomerUserController@indexCustomerRelated');
	Route::match(['get', 'post'], 'customer-user/download', 'AdeN\Api\Modules\Customer\User\Http\Controllers\CustomerUserController@download');
    Route::match(['get', 'post'], 'customer-user/download-template', 'AdeN\Api\Modules\Customer\User\Http\Controllers\CustomerUserController@downloadTemplate');