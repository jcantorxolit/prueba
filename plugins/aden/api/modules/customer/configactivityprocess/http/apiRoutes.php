<?php
    
	/**
     *Module: CustomerConfigActivityProcess
     */
    Route::get('customer-config-activity-process/get', 'AdeN\Api\Modules\Customer\ConfigActivityProcess\Http\Controllers\CustomerConfigActivityProcessController@show');
    Route::post('customer-config-activity-process/save', 'AdeN\Api\Modules\Customer\ConfigActivityProcess\Http\Controllers\CustomerConfigActivityProcessController@store');
    Route::post('customer-config-activity-process/delete', 'AdeN\Api\Modules\Customer\ConfigActivityProcess\Http\Controllers\CustomerConfigActivityProcessController@destroy');
    Route::post('customer-config-activity-process/import', 'AdeN\Api\Modules\Customer\ConfigActivityProcess\Http\Controllers\CustomerConfigActivityProcessController@import');
    Route::post('customer-config-activity-process/upload', 'AdeN\Api\Modules\Customer\ConfigActivityProcess\Http\Controllers\CustomerConfigActivityProcessController@upload');
    Route::match(['get', 'post'], 'customer-config-activity-process', 'AdeN\Api\Modules\Customer\ConfigActivityProcess\Http\Controllers\CustomerConfigActivityProcessController@index');
	Route::match(['get', 'post'], 'customer-config-activity-process/download', 'AdeN\Api\Modules\Customer\ConfigActivityProcess\Http\Controllers\CustomerConfigActivityProcessController@download');