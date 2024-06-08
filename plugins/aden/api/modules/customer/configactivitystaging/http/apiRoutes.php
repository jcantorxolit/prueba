<?php
    
	/**
     *Module: CustomerConfigActivityStaging
     */
    Route::get('customer-config-activity-staging/get', 'AdeN\Api\Modules\Customer\ConfigActivityStaging\Http\Controllers\CustomerConfigActivityStagingController@show');
    Route::post('customer-config-activity-staging/save', 'AdeN\Api\Modules\Customer\ConfigActivityStaging\Http\Controllers\CustomerConfigActivityStagingController@store');
    Route::post('customer-config-activity-staging/delete', 'AdeN\Api\Modules\Customer\ConfigActivityStaging\Http\Controllers\CustomerConfigActivityStagingController@destroy');
    Route::post('customer-config-activity-staging/import', 'AdeN\Api\Modules\Customer\ConfigActivityStaging\Http\Controllers\CustomerConfigActivityStagingController@import');
    Route::post('customer-config-activity-staging/upload', 'AdeN\Api\Modules\Customer\ConfigActivityStaging\Http\Controllers\CustomerConfigActivityStagingController@upload');
    Route::match(['get', 'post'], 'customer-config-activity-staging', 'AdeN\Api\Modules\Customer\ConfigActivityStaging\Http\Controllers\CustomerConfigActivityStagingController@index');
	Route::match(['get', 'post'], 'customer-config-activity-staging/download', 'AdeN\Api\Modules\Customer\ConfigActivityStaging\Http\Controllers\CustomerConfigActivityStagingController@download');