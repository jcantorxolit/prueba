<?php
    
	/**
     *Module: CustomerEmployeeDemographicStaging
     */
    Route::get('customer-employee-demographic-staging/get', 'AdeN\Api\Modules\Customer\Employee\DemographicStaging\Http\Controllers\CustomerEmployeeDemographicStagingController@show');
    Route::post('customer-employee-demographic-staging/save', 'AdeN\Api\Modules\Customer\Employee\DemographicStaging\Http\Controllers\CustomerEmployeeDemographicStagingController@store');
    Route::post('customer-employee-demographic-staging/update', 'AdeN\Api\Modules\Customer\Employee\DemographicStaging\Http\Controllers\CustomerEmployeeDemographicStagingController@update');    
    Route::post('customer-employee-demographic-staging/delete', 'AdeN\Api\Modules\Customer\Employee\DemographicStaging\Http\Controllers\CustomerEmployeeDemographicStagingController@destroy');
    Route::post('customer-employee-demographic-staging/import', 'AdeN\Api\Modules\Customer\Employee\DemographicStaging\Http\Controllers\CustomerEmployeeDemographicStagingController@import');
    Route::post('customer-employee-demographic-staging/upload', 'AdeN\Api\Modules\Customer\Employee\DemographicStaging\Http\Controllers\CustomerEmployeeDemographicStagingController@upload');
    Route::match(['get', 'post'], 'customer-employee-demographic-staging', 'AdeN\Api\Modules\Customer\Employee\DemographicStaging\Http\Controllers\CustomerEmployeeDemographicStagingController@index');
    Route::match(['get', 'post'], 'customer-employee-demographic-staging/download', 'AdeN\Api\Modules\Customer\Employee\DemographicStaging\Http\Controllers\CustomerEmployeeDemographicStagingController@download');
    Route::match(['get', 'post'], 'customer-employee-demographic-staging/download-template', 'AdeN\Api\Modules\Customer\Employee\DemographicStaging\Http\Controllers\CustomerEmployeeDemographicStagingController@downloadTemplate');    