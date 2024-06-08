<?php
    
	/**
     *Module: CustomerAbsenteeismDisabilityStaging
     */
    Route::get('customer-absenteeism-disability-staging/get', 'AdeN\Api\Modules\Customer\AbsenteeismDisabilityStaging\Http\Controllers\CustomerAbsenteeismDisabilityStagingController@show');
    Route::post('customer-absenteeism-disability-staging/save', 'AdeN\Api\Modules\Customer\AbsenteeismDisabilityStaging\Http\Controllers\CustomerAbsenteeismDisabilityStagingController@store');
    Route::post('customer-absenteeism-disability-staging/update', 'AdeN\Api\Modules\Customer\AbsenteeismDisabilityStaging\Http\Controllers\CustomerAbsenteeismDisabilityStagingController@update');    
    Route::post('customer-absenteeism-disability-staging/delete', 'AdeN\Api\Modules\Customer\AbsenteeismDisabilityStaging\Http\Controllers\CustomerAbsenteeismDisabilityStagingController@destroy');
    Route::post('customer-absenteeism-disability-staging/import', 'AdeN\Api\Modules\Customer\AbsenteeismDisabilityStaging\Http\Controllers\CustomerAbsenteeismDisabilityStagingController@import');
    Route::post('customer-absenteeism-disability-staging/upload', 'AdeN\Api\Modules\Customer\AbsenteeismDisabilityStaging\Http\Controllers\CustomerAbsenteeismDisabilityStagingController@upload');
    Route::match(['get', 'post'], 'customer-absenteeism-disability-staging', 'AdeN\Api\Modules\Customer\AbsenteeismDisabilityStaging\Http\Controllers\CustomerAbsenteeismDisabilityStagingController@index');
	Route::match(['get', 'post'], 'customer-absenteeism-disability-staging/download', 'AdeN\Api\Modules\Customer\AbsenteeismDisabilityStaging\Http\Controllers\CustomerAbsenteeismDisabilityStagingController@download');