<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-absenteeism-disability', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@show');
    Route::get('customer-absenteeism-disability/get', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@showWithFormat');
    Route::post('customer-absenteeism-disability/save', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@store');
    Route::post('customer-absenteeism-disability/update', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@update');
    Route::post('customer-absenteeism-disability/delete', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@destroy');
    Route::post('customer-absenteeism-disability/import', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@import');
    Route::post('customer-absenteeism-disability/upload', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@upload');
    Route::match(['post'], 'customer-absenteeism-disability', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@index');
    Route::match(['post'], 'customer-absenteeism-disability-related', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@indexRelated');
    Route::match(['post'], 'customer-absenteeism-disability-diagnostic-analysis', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@indexDiagnosticAnalysis');
    Route::match(['post'], 'customer-absenteeism-disability-sumary', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@indexDiagnosticSummary');
    Route::match(['post'], 'customer-absenteeism-disability-general', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@indexDisabilityGeneral');
    Route::match(['post'], 'customer-absenteeism-disability-person-analysis', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@indexPersonAnalysis');
    Route::match(['post'], 'customer-absenteeism-disability-days-analysis', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@indexDaysAnalysis');
	Route::match(['get', 'post'], 'customer-absenteeism-disability/download', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@download');
	Route::match(['get', 'post'], 'customer-absenteeism-disability/export-general', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@exportGeneral');
	Route::match(['get', 'post'], 'customer-absenteeism-disability/export-excel', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@exportExcel');
    Route::match(['get', 'post'], 'customer-absenteeism-disability/export-person-analysis', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@exportPersonAnalysis');
    Route::match(['get', 'post'], 'customer-absenteeism-disability/download-template', 'AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers\CustomerAbsenteeismDisabilityController@downloadTemplate');        