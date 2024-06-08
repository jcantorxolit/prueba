<?php

	/**
     *Module: CustomerVrEmployee
     */
    Route::get('customer-vr-employee/get', 'AdeN\Api\Modules\Customer\VrEmployee\Http\Controllers\CustomerVrEmployeeController@show');
    Route::post('customer-vr-employee/save', 'AdeN\Api\Modules\Customer\VrEmployee\Http\Controllers\CustomerVrEmployeeController@store');
    Route::post('customer-vr-employee/cancel', 'AdeN\Api\Modules\Customer\VrEmployee\Http\Controllers\CustomerVrEmployeeController@cancel');
    Route::post('customer-vr-employee/consolidate', 'AdeN\Api\Modules\Customer\VrEmployee\Http\Controllers\CustomerVrEmployeeController@consolidate');
    Route::match(['get', 'post'], 'customer-vr-employee', 'AdeN\Api\Modules\Customer\VrEmployee\Http\Controllers\CustomerVrEmployeeController@index');
    Route::match(['get', 'post'], 'customer-vr-employee-staging', 'AdeN\Api\Modules\Customer\VrEmployee\Http\Controllers\CustomerVrEmployeeController@indexStaging');
    Route::match(['get', 'post'], 'customer-vr-employee/detail', 'AdeN\Api\Modules\Customer\VrEmployee\Http\Controllers\CustomerVrEmployeeController@indexExperienceDetail');
	Route::match(['get', 'post'], 'customer-vr-employee/export', 'AdeN\Api\Modules\Customer\VrEmployee\Http\Controllers\CustomerVrEmployeeController@export');
	Route::match(['get', 'post'], 'customer-vr-employee/export-indicators', 'AdeN\Api\Modules\Customer\VrEmployee\Http\Controllers\CustomerVrEmployeeController@exportIndicators');
    Route::match(['get', 'post'], 'customer-vr-employee/export-certificate', 'AdeN\Api\Modules\Customer\VrEmployee\Http\Controllers\CustomerVrEmployeeController@exportCertificate');
	Route::match(['get', 'post'], 'customer-vr-employee/download-template', 'AdeN\Api\Modules\Customer\VrEmployee\Http\Controllers\CustomerVrEmployeeController@downloadTemplate');
	Route::match(['get', 'post'], 'customer-vr-employee-head/download-template', 'AdeN\Api\Modules\Customer\VrEmployee\Http\Controllers\CustomerVrEmployeeController@downloadTemplateEmployee');
    Route::match(['get', 'post'], 'customer-vr-employee/generate-report-pdf', 'AdeN\Api\Modules\Customer\VrEmployee\Http\Controllers\CustomerVrEmployeeController@generateReportPdf');
