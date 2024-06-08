<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-config-job', 'AdeN\Api\Modules\Customer\ConfigJobProcess\Http\Controllers\CustomerConfigJobController@show');
    Route::post('customer-config-job/save', 'AdeN\Api\Modules\Customer\ConfigJobProcess\Http\Controllers\CustomerConfigJobController@store');
    Route::post('customer-config-job/update', 'AdeN\Api\Modules\Customer\ConfigJobProcess\Http\Controllers\CustomerConfigJobController@update');
    Route::post('customer-config-job/delete', 'AdeN\Api\Modules\Customer\ConfigJobProcess\Http\Controllers\CustomerConfigJobController@destroy');
    Route::post('customer-config-job/import', 'AdeN\Api\Modules\Customer\ConfigJobProcess\Http\Controllers\CustomerConfigJobController@import');
    Route::post('customer-config-job/upload', 'AdeN\Api\Modules\Customer\ConfigJobProcess\Http\Controllers\CustomerConfigJobController@upload');
    Route::match(['post'], 'customer-config-job', 'AdeN\Api\Modules\Customer\ConfigJobProcess\Http\Controllers\CustomerConfigJobController@index');
	Route::match(['get', 'post'], 'customer-config-job/download', 'AdeN\Api\Modules\Customer\ConfigJobProcess\Http\Controllers\CustomerConfigJobController@download');