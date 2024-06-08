<?php
    
	/**
     *Module: CustomerAbsenteeismDisabilityDayCharged
     */
    Route::get('customer-absenteeism-disability-day-charged/get', 'AdeN\Api\Modules\Customer\AbsenteeismDisabilityDayCharged\Http\Controllers\CustomerAbsenteeismDisabilityDayChargedController@show');
    Route::post('customer-absenteeism-disability-day-charged/save', 'AdeN\Api\Modules\Customer\AbsenteeismDisabilityDayCharged\Http\Controllers\CustomerAbsenteeismDisabilityDayChargedController@store');
    Route::post('customer-absenteeism-disability-day-charged/delete', 'AdeN\Api\Modules\Customer\AbsenteeismDisabilityDayCharged\Http\Controllers\CustomerAbsenteeismDisabilityDayChargedController@destroy');
    Route::post('customer-absenteeism-disability-day-charged/import', 'AdeN\Api\Modules\Customer\AbsenteeismDisabilityDayCharged\Http\Controllers\CustomerAbsenteeismDisabilityDayChargedController@import');
    Route::post('customer-absenteeism-disability-day-charged/upload', 'AdeN\Api\Modules\Customer\AbsenteeismDisabilityDayCharged\Http\Controllers\CustomerAbsenteeismDisabilityDayChargedController@upload');
    Route::match(['get', 'post'], 'customer-absenteeism-disability-day-charged', 'AdeN\Api\Modules\Customer\AbsenteeismDisabilityDayCharged\Http\Controllers\CustomerAbsenteeismDisabilityDayChargedController@index');
    Route::match(['get', 'post'], 'customer-absenteeism-disability-day-charged-available', 'AdeN\Api\Modules\Customer\AbsenteeismDisabilityDayCharged\Http\Controllers\CustomerAbsenteeismDisabilityDayChargedController@indexAvailable');
	Route::match(['get', 'post'], 'customer-absenteeism-disability-day-charged/download', 'AdeN\Api\Modules\Customer\AbsenteeismDisabilityDayCharged\Http\Controllers\CustomerAbsenteeismDisabilityDayChargedController@download');