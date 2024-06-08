<?php

	/**
     *Module: CustomerEmployeeCriticalActivity
     */
    Route::post('customer-employee-critical-activity/get', 'AdeN\Api\Modules\Customer\Employee\CriticalActivity\Http\Controllers\CustomerEmployeeCriticalActivityController@show');
    Route::post('customer-employee-critical-activity/save', 'AdeN\Api\Modules\Customer\Employee\CriticalActivity\Http\Controllers\CustomerEmployeeCriticalActivityController@store');
    Route::post('customer-employee-critical-activity/bulk', 'AdeN\Api\Modules\Customer\Employee\CriticalActivity\Http\Controllers\CustomerEmployeeCriticalActivityController@bulk');
    Route::post('customer-employee-critical-activity/delete', 'AdeN\Api\Modules\Customer\Employee\CriticalActivity\Http\Controllers\CustomerEmployeeCriticalActivityController@destroy');
    Route::post('customer-employee-critical-activity/export-by-type', 'AdeN\Api\Modules\Customer\Employee\CriticalActivity\Http\Controllers\CustomerEmployeeCriticalActivityController@exportByType');
    Route::get('customer-employee-critical-activity/download-template', 'AdeN\Api\Modules\Customer\Employee\CriticalActivity\Http\Controllers\CustomerEmployeeCriticalActivityController@downloadTemplate');
    Route::match(['post'], 'customer-employee-critical-activity/available', 'AdeN\Api\Modules\Customer\Employee\CriticalActivity\Http\Controllers\CustomerEmployeeCriticalActivityController@indexAvailable');
    Route::match(['post'], 'customer-employee-critical-activity', 'AdeN\Api\Modules\Customer\Employee\CriticalActivity\Http\Controllers\CustomerEmployeeCriticalActivityController@index');
