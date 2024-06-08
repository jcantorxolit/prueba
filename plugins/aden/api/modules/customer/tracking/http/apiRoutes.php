<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-tracking', 'AdeN\Api\Modules\Customer\Tracking\Http\Controllers\CustomerTrackingController@show');
    Route::post('customer-tracking/save', 'AdeN\Api\Modules\Customer\Tracking\Http\Controllers\CustomerTrackingController@store');
    Route::post('customer-tracking/update', 'AdeN\Api\Modules\Customer\Tracking\Http\Controllers\CustomerTrackingController@update');
    Route::post('customer-tracking/delete', 'AdeN\Api\Modules\Customer\Tracking\Http\Controllers\CustomerTrackingController@destroy');
    Route::post('customer-tracking/import', 'AdeN\Api\Modules\Customer\Tracking\Http\Controllers\CustomerTrackingController@import');
    Route::post('customer-tracking/upload', 'AdeN\Api\Modules\Customer\Tracking\Http\Controllers\CustomerTrackingController@upload');
    Route::match(['post'], 'customer-tracking', 'AdeN\Api\Modules\Customer\Tracking\Http\Controllers\CustomerTrackingController@index');
    Route::match(['post'], 'customer-tracking-comment', 'AdeN\Api\Modules\Customer\Tracking\Http\Controllers\CustomerTrackingController@indexComment');
	Route::match(['get', 'post'], 'customer-tracking/download', 'AdeN\Api\Modules\Customer\Tracking\Http\Controllers\CustomerTrackingController@download');