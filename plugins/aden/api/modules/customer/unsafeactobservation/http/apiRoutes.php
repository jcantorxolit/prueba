<?php
    
	/**
     *Module: CustomerUnsafeActObservation
     */
    Route::get('customer-unsafe-act-observation/get', 'AdeN\Api\Modules\Customer\UnsafeActObservation\Http\Controllers\CustomerUnsafeActObservationController@show');
    Route::post('customer-unsafe-act-observation/save', 'AdeN\Api\Modules\Customer\UnsafeActObservation\Http\Controllers\CustomerUnsafeActObservationController@store');
    Route::post('customer-unsafe-act-observation/delete', 'AdeN\Api\Modules\Customer\UnsafeActObservation\Http\Controllers\CustomerUnsafeActObservationController@destroy');
    Route::post('customer-unsafe-act-observation/import', 'AdeN\Api\Modules\Customer\UnsafeActObservation\Http\Controllers\CustomerUnsafeActObservationController@import');
    Route::post('customer-unsafe-act-observation/upload', 'AdeN\Api\Modules\Customer\UnsafeActObservation\Http\Controllers\CustomerUnsafeActObservationController@upload');
    Route::match(['get', 'post'], 'customer-unsafe-act-observation', 'AdeN\Api\Modules\Customer\UnsafeActObservation\Http\Controllers\CustomerUnsafeActObservationController@index');
	Route::match(['get', 'post'], 'customer-unsafe-act-observation/download', 'AdeN\Api\Modules\Customer\UnsafeActObservation\Http\Controllers\CustomerUnsafeActObservationController@download');