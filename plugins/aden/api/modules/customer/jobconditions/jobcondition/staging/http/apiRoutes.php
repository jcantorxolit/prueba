<?php

	/**
     *Module: CustomerEmployeeStaging
     */
    Route::get('job-conditions-staging/get', 'Aden\Api\Modules\Customer\Jobconditions\Jobcondition\Staging\Http\Controllers\JobConditionsStagingController@show');
    Route::post('job-conditions-staging/save', 'Aden\Api\Modules\Customer\Jobconditions\Jobcondition\Staging\Http\Controllers\JobConditionsStagingController@store');
    Route::post('job-conditions-staging/update', 'Aden\Api\Modules\Customer\Jobconditions\Jobcondition\Staging\Http\Controllers\JobConditionsStagingController@update');

    Route::match(['get', 'post'], 'job-conditions-staging', 'Aden\Api\Modules\Customer\Jobconditions\Jobcondition\Staging\Http\Controllers\JobConditionsStagingController@index');
