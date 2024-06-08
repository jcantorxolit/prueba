<?php
    
	/**
     *Module: CustomerConfigJobActivity
     */
    Route::get('customer-config-job-activity/get', 'AdeN\Api\Modules\Customer\ConfigJobActivity\Http\Controllers\CustomerConfigJobActivityController@show');
    Route::post('customer-config-job-activity/save', 'AdeN\Api\Modules\Customer\ConfigJobActivity\Http\Controllers\CustomerConfigJobActivityController@store');
    Route::post('customer-config-job-activity/delete', 'AdeN\Api\Modules\Customer\ConfigJobActivity\Http\Controllers\CustomerConfigJobActivityController@destroy');
    Route::post('customer-config-job-activity/import', 'AdeN\Api\Modules\Customer\ConfigJobActivity\Http\Controllers\CustomerConfigJobActivityController@import');
    Route::post('customer-config-job-activity/upload', 'AdeN\Api\Modules\Customer\ConfigJobActivity\Http\Controllers\CustomerConfigJobActivityController@upload');
    Route::match(['get', 'post'], 'customer-config-job-activity', 'AdeN\Api\Modules\Customer\ConfigJobActivity\Http\Controllers\CustomerConfigJobActivityController@index');
	Route::match(['get', 'post'], 'customer-config-job-activity/download', 'AdeN\Api\Modules\Customer\ConfigJobActivity\Http\Controllers\CustomerConfigJobActivityController@download');