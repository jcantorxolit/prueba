<?php

/**
 *Module: Job Conditions - Evaluation - Interventions
 */
Route::post('customer-jobconditions/intervention/', 'AdeN\Api\Modules\Customer\JobConditions\Intervention\Http\Controllers\InterventionController@index');
Route::post('customer-jobconditions/intervention/save', 'AdeN\Api\Modules\Customer\JobConditions\Intervention\Http\Controllers\InterventionController@store');
Route::post('customer-jobconditions/intervention/upload', 'AdeN\Api\Modules\Customer\JobConditions\Intervention\Http\Controllers\InterventionController@upload');
Route::post('customer-jobconditions/intervention/close', 'AdeN\Api\Modules\Customer\JobConditions\Intervention\Http\Controllers\InterventionController@closeIntervention');
Route::post('customer-jobconditions/intervention/show', 'AdeN\Api\Modules\Customer\JobConditions\Intervention\Http\Controllers\InterventionController@show');
