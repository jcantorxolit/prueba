<?php
    
	/**
     *Module: CustomerConfigJobActivityStaging
     */
    Route::get('customer-config-job-activity-staging/get', 'AdeN\Api\Modules\Customer\ConfigJobActivityStaging\Http\Controllers\CustomerConfigJobActivityStagingController@show');
    Route::post('customer-config-job-activity-staging/save', 'AdeN\Api\Modules\Customer\ConfigJobActivityStaging\Http\Controllers\CustomerConfigJobActivityStagingController@store');
    Route::post('customer-config-job-activity-staging/delete', 'AdeN\Api\Modules\Customer\ConfigJobActivityStaging\Http\Controllers\CustomerConfigJobActivityStagingController@destroy');
    Route::post('customer-config-job-activity-staging/import', 'AdeN\Api\Modules\Customer\ConfigJobActivityStaging\Http\Controllers\CustomerConfigJobActivityStagingController@import');
    Route::post('customer-config-job-activity-staging/upload', 'AdeN\Api\Modules\Customer\ConfigJobActivityStaging\Http\Controllers\CustomerConfigJobActivityStagingController@upload');
    Route::match(['get', 'post'], 'customer-config-job-activity-staging', 'AdeN\Api\Modules\Customer\ConfigJobActivityStaging\Http\Controllers\CustomerConfigJobActivityStagingController@index');
	Route::match(['get', 'post'], 'customer-config-job-activity-staging/download', 'AdeN\Api\Modules\Customer\ConfigJobActivityStaging\Http\Controllers\CustomerConfigJobActivityStagingController@download');
	Route::match(['get', 'post'], 'customer-config-job-activity-staging/download-template', 'AdeN\Api\Modules\Customer\ConfigJobActivityStaging\Http\Controllers\CustomerConfigJobActivityStagingController@downloadTemplate');