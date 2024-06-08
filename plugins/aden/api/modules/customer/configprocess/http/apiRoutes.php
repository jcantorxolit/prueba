<?php
    
	/**
     *Module: CustomerConfigProcess
     */
    Route::get('customer-config-process/get', 'AdeN\Api\Modules\Customer\ConfigProcess\Http\Controllers\CustomerConfigProcessController@show');
    Route::post('customer-config-process/save', 'AdeN\Api\Modules\Customer\ConfigProcess\Http\Controllers\CustomerConfigProcessController@store');
    Route::post('customer-config-process/delete', 'AdeN\Api\Modules\Customer\ConfigProcess\Http\Controllers\CustomerConfigProcessController@destroy');
    Route::post('customer-config-process/import', 'AdeN\Api\Modules\Customer\ConfigProcess\Http\Controllers\CustomerConfigProcessController@import');
    Route::post('customer-config-process/upload', 'AdeN\Api\Modules\Customer\ConfigProcess\Http\Controllers\CustomerConfigProcessController@upload');
    Route::match(['get', 'post'], 'customer-config-process', 'AdeN\Api\Modules\Customer\ConfigProcess\Http\Controllers\CustomerConfigProcessController@index');
	Route::match(['get', 'post'], 'customer-config-process/download', 'AdeN\Api\Modules\Customer\ConfigProcess\Http\Controllers\CustomerConfigProcessController@download');
	Route::match(['get', 'post'], 'customer-config-process/download-template', 'AdeN\Api\Modules\Customer\ConfigProcess\Http\Controllers\CustomerConfigProcessController@downloadTemplate');