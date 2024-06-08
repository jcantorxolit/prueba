<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-absenteeism-indicator', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@show');
    Route::post('customer-absenteeism-indicator/save', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@store');
    Route::post('customer-absenteeism-indicator/update', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@update');
    // Route::post('customer-absenteeism-indicator/delete', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@destroy');
    // Route::post('customer-absenteeism-indicator/import', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@import');
    // Route::post('customer-absenteeism-indicator/upload', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@upload');
    Route::post('customer-absenteeism-indicator/consolidate-1111', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@consolidate');
    Route::post('customer-absenteeism-indicator/consolidate-0312', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@consolidate');
    // Route::match(['post'], 'customer-absenteeism-indicator', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@index');
    Route::match(['post'], 'customer-absenteeism-indicator', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@index');
    Route::match(['post'], 'customer-absenteeism-indicator-summary', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@indexSummary');
    Route::match(['post'], 'customer-absenteeism-indicator-parent', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@indexParent');
    Route::match(['post'], 'customer-absenteeism-indicator-detail', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@indexDetail');
    Route::match(['post'], 'customer-absenteeism-indicator-frequency-accidentality', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@indexFrequencyAccidentality');
    Route::match(['post'], 'customer-absenteeism-indicator-severity-accidentality', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@indexSeverityAccidentality');
    Route::match(['post'], 'customer-absenteeism-indicator-mortal-proportion-accidentality', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@indexMortalProportionAccidentality');
    Route::match(['post'], 'customer-absenteeism-indicator-absenteeism-medical-cause', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@indexAbsenteeismMedicalCause');
    Route::match(['post'], 'customer-absenteeism-indicator-occupational-disease-fatality-rate', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@indexOccupationalDiseaseFatalityRate');
    Route::match(['post'], 'customer-absenteeism-indicator-occupational-disease-prevalence', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@indexOccupationalDiseasePrevalence');
    Route::match(['post'], 'customer-absenteeism-indicator-occupational-disease-incidence', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@indexOccupationalDiseaseIncidence');

    // Route::match(['get', 'post'], 'customer-absenteeism-indicator/download', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@download');
    Route::match(['get', 'post'], 'customer-absenteeism-indicator/export-parent', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@exportParent');
    Route::match(['get', 'post'], 'customer-absenteeism-indicator/export-frequency-accidentality', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@export');
    Route::match(['get', 'post'], 'customer-absenteeism-indicator/export-severity-accidentality', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@export');
    Route::match(['get', 'post'], 'customer-absenteeism-indicator/export-mortal-proportion-accidentality', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@export');
    Route::match(['get', 'post'], 'customer-absenteeism-indicator/export-absenteeism-medical-cause', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@export');
    Route::match(['get', 'post'], 'customer-absenteeism-indicator/export-occupational-disease-fatality-rate', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@export');
    Route::match(['get', 'post'], 'customer-absenteeism-indicator/export-occupational-disease-prevalence', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@export');
    Route::match(['get', 'post'], 'customer-absenteeism-indicator/export-occupational-disease-incidence', 'AdeN\Api\Modules\Customer\AbsenteeismIndicator\Http\Controllers\CustomerAbsenteeismIndicatorController@export');