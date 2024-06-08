<?php
    
	/**
     *Module: CustomerConfigProcessStaging
     */
    Route::get('customer-config-process-staging/get', 'AdeN\Api\Modules\Customer\ConfigProcessStaging\Http\Controllers\CustomerConfigProcessStagingController@show');
    Route::post('customer-config-process-staging/save', 'AdeN\Api\Modules\Customer\ConfigProcessStaging\Http\Controllers\CustomerConfigProcessStagingController@store');
    Route::post('customer-config-process-staging/delete', 'AdeN\Api\Modules\Customer\ConfigProcessStaging\Http\Controllers\CustomerConfigProcessStagingController@destroy');
    Route::post('customer-config-process-staging/import', 'AdeN\Api\Modules\Customer\ConfigProcessStaging\Http\Controllers\CustomerConfigProcessStagingController@import');
    Route::post('customer-config-process-staging/upload', 'AdeN\Api\Modules\Customer\ConfigProcessStaging\Http\Controllers\CustomerConfigProcessStagingController@upload');
    Route::match(['get', 'post'], 'customer-config-process-staging', 'AdeN\Api\Modules\Customer\ConfigProcessStaging\Http\Controllers\CustomerConfigProcessStagingController@index');
	Route::match(['get', 'post'], 'customer-config-process-staging/download', 'AdeN\Api\Modules\Customer\ConfigProcessStaging\Http\Controllers\CustomerConfigProcessStagingController@download');