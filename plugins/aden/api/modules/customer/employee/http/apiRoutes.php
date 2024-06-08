<?php

	/**
     *Module: CustomerEmployee
     */
    Route::get('event-customer-employee', 'AdeN\Api\Modules\Customer\Employee\Http\Controllers\CustomerEmployeeController@show');
    Route::post('event-customer-employee/save', 'AdeN\Api\Modules\Customer\Employee\Http\Controllers\CustomerEmployeeController@store');
    Route::post('customer-employee/update', 'AdeN\Api\Modules\Customer\Employee\Http\Controllers\CustomerEmployeeController@update');
    Route::post('customer-employee/save-auth', 'AdeN\Api\Modules\Customer\Employee\Http\Controllers\CustomerEmployeeController@updateAuthStatus');
    Route::post('customer-employee/backup-recovery', 'AdeN\Api\Modules\Customer\Employee\Http\Controllers\CustomerEmployeeController@backupRecovery');
    Route::post('event-customer-employee/delete', 'AdeN\Api\Modules\Customer\Employee\Http\Controllers\CustomerEmployeeController@destroy');
    Route::post('event-customer-employee/import', 'AdeN\Api\Modules\Customer\Employee\Http\Controllers\CustomerEmployeeController@import');
    Route::post('event-customer-employee/upload', 'AdeN\Api\Modules\Customer\Employee\Http\Controllers\CustomerEmployeeController@upload');
    Route::post('customer-employee-less/import', 'AdeN\Api\Modules\Customer\Employee\Http\Controllers\CustomerEmployeeController@lessImport');
    Route::match(['post'], 'customer-employee-v2', 'AdeN\Api\Modules\Customer\Employee\Http\Controllers\CustomerEmployeeController@index');
    Route::match(['post'], 'customer-employee-modal-basic', 'AdeN\Api\Modules\Customer\Employee\Http\Controllers\CustomerEmployeeController@indexModalBasic');
    Route::match(['post'], 'customer-employee-modal-basic-2', 'AdeN\Api\Modules\Customer\Employee\Http\Controllers\CustomerEmployeeController@indexModalBasic2');
    Route::match(['get', 'post'], 'event-customer-employee/download', 'AdeN\Api\Modules\Customer\Employee\Http\Controllers\CustomerEmployeeController@download');
    Route::match(['get', 'post'], 'customer-employee-less/download-template', 'AdeN\Api\Modules\Customer\Employee\Http\Controllers\CustomerEmployeeController@downloadDocument');
    Route::match(['post'], 'customer-employee-less', 'AdeN\Api\Modules\Customer\Employee\Http\Controllers\CustomerEmployeeController@indexLess');
