<?php
    
	/**
     *Module: CustomerEmployeeStaging
     */
    Route::get('customer-employee-staging/get', 'AdeN\Api\Modules\Customer\Employee\Staging\Http\Controllers\CustomerEmployeeStagingController@show');
    Route::post('customer-employee-staging/save', 'AdeN\Api\Modules\Customer\Employee\Staging\Http\Controllers\CustomerEmployeeStagingController@store');
    Route::post('customer-employee-staging/update', 'AdeN\Api\Modules\Customer\Employee\Staging\Http\Controllers\CustomerEmployeeStagingController@update');    
    Route::post('customer-employee-staging/delete', 'AdeN\Api\Modules\Customer\Employee\Staging\Http\Controllers\CustomerEmployeeStagingController@destroy');
    Route::post('customer-employee-staging/import', 'AdeN\Api\Modules\Customer\Employee\Staging\Http\Controllers\CustomerEmployeeStagingController@import');
    Route::post('customer-employee-staging/upload', 'AdeN\Api\Modules\Customer\Employee\Staging\Http\Controllers\CustomerEmployeeStagingController@upload');
    Route::match(['get', 'post'], 'customer-employee-staging', 'AdeN\Api\Modules\Customer\Employee\Staging\Http\Controllers\CustomerEmployeeStagingController@index');
    Route::match(['get', 'post'], 'customer-employee-staging/download', 'AdeN\Api\Modules\Customer\Employee\Staging\Http\Controllers\CustomerEmployeeStagingController@download');