<?php
    
	/**
     *Module: CustomerConfigJobExpress
     */
    Route::get('customer-config-job-express/get', 'AdeN\Api\Modules\Customer\ConfigJobExpress\Http\Controllers\CustomerConfigJobExpressController@show');
    Route::post('customer-config-job-express/save', 'AdeN\Api\Modules\Customer\ConfigJobExpress\Http\Controllers\CustomerConfigJobExpressController@store');
    Route::post('customer-config-job-express/delete', 'AdeN\Api\Modules\Customer\ConfigJobExpress\Http\Controllers\CustomerConfigJobExpressController@destroy');
    Route::post('customer-config-job-express/import', 'AdeN\Api\Modules\Customer\ConfigJobExpress\Http\Controllers\CustomerConfigJobExpressController@import');
    Route::post('customer-config-job-express/upload', 'AdeN\Api\Modules\Customer\ConfigJobExpress\Http\Controllers\CustomerConfigJobExpressController@upload');
    Route::match(['get', 'post'], 'customer-config-job-express', 'AdeN\Api\Modules\Customer\ConfigJobExpress\Http\Controllers\CustomerConfigJobExpressController@index');
	Route::match(['get', 'post'], 'customer-config-job-express/download', 'AdeN\Api\Modules\Customer\ConfigJobExpress\Http\Controllers\CustomerConfigJobExpressController@download');