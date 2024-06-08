<?php

/**
 *Module: Job Conditions
 */
Route::post('customer-jobconditions', 'AdeN\Api\Modules\Customer\JobConditions\JobCondition\Http\Controllers\JobConditionController@index');
Route::post('customer-jobconditions/show', 'AdeN\Api\Modules\Customer\JobConditions\JobCondition\Http\Controllers\JobConditionController@show');
Route::post('customer-jobconditions/save', 'AdeN\Api\Modules\Customer\JobConditions\JobCondition\Http\Controllers\JobConditionController@store');
Route::match(['get', 'post'],'customer-jobconditions/download-template', 'AdeN\Api\Modules\Customer\JobConditions\JobCondition\Http\Controllers\JobConditionController@downloadTemplate');

Route::post('customer-jobconditions/config', 'AdeN\Api\Modules\Customer\JobConditions\JobCondition\Http\Controllers\JobConditionController@config');
