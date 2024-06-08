<?php
    
	/**
     *Module: CustomerConfigActivity
     */
    Route::get('customer-config-activity/get', 'AdeN\Api\Modules\Customer\ConfigActivity\Http\Controllers\CustomerConfigActivityController@show');
    Route::post('customer-config-activity/save', 'AdeN\Api\Modules\Customer\ConfigActivity\Http\Controllers\CustomerConfigActivityController@store');
    Route::post('customer-config-activity/delete', 'AdeN\Api\Modules\Customer\ConfigActivity\Http\Controllers\CustomerConfigActivityController@destroy');
    Route::post('customer-config-activity/import', 'AdeN\Api\Modules\Customer\ConfigActivity\Http\Controllers\CustomerConfigActivityController@import');
    Route::post('customer-config-activity/upload', 'AdeN\Api\Modules\Customer\ConfigActivity\Http\Controllers\CustomerConfigActivityController@upload');
    Route::match(['get', 'post'], 'customer-config-activity', 'AdeN\Api\Modules\Customer\ConfigActivity\Http\Controllers\CustomerConfigActivityController@index');
	Route::match(['get', 'post'], 'customer-config-activity/download', 'AdeN\Api\Modules\Customer\ConfigActivity\Http\Controllers\CustomerConfigActivityController@download');
	Route::match(['get', 'post'], 'customer-config-activity/download-template', 'AdeN\Api\Modules\Customer\ConfigActivity\Http\Controllers\CustomerConfigActivityController@downloadTemplate');