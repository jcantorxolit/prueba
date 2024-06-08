<?php
    
	/**
     *Module: CustomerInternalProject
     */
    Route::get('customer-internal-project/get', 'AdeN\Api\Modules\Customer\InternalProject\Http\Controllers\CustomerInternalProjectController@show');
    Route::post('customer-internal-project/save', 'AdeN\Api\Modules\Customer\InternalProject\Http\Controllers\CustomerInternalProjectController@store');
    Route::post('customer-internal-project/delete', 'AdeN\Api\Modules\Customer\InternalProject\Http\Controllers\CustomerInternalProjectController@destroy');
    Route::post('customer-internal-project/import', 'AdeN\Api\Modules\Customer\InternalProject\Http\Controllers\CustomerInternalProjectController@import');
    Route::post('customer-internal-project/upload', 'AdeN\Api\Modules\Customer\InternalProject\Http\Controllers\CustomerInternalProjectController@upload');
    Route::match(['get', 'post'], 'customer-internal-project', 'AdeN\Api\Modules\Customer\InternalProject\Http\Controllers\CustomerInternalProjectController@index');
	Route::match(['get', 'post'], 'customer-internal-project/download', 'AdeN\Api\Modules\Customer\InternalProject\Http\Controllers\CustomerInternalProjectController@download');