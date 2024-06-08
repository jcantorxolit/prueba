<?php

/**
 *Module: Job Conditions - Indicators
 */
Route::post('customer-jobconditions/indicators/get-dates-evaluations-by-employees', 'AdeN\Api\Modules\Customer\JobConditions\Indicator\Http\Controllers\IndicatorController@getDatesEvaluationsByEmployees');
Route::post('customer-jobconditions/indicators/get-indicator-by-evaluation', 'AdeN\Api\Modules\Customer\JobConditions\Indicator\Http\Controllers\IndicatorController@getIndicatorByEvaluation');
Route::match(['get', 'post'], 'customer-jobconditions/indicators/export-excel', 'AdeN\Api\Modules\Customer\JobConditions\Indicator\Http\Controllers\IndicatorController@dowloadExcel');

/*Indicador general*/
Route::post('customer-jobconditions/indicators/get-year-evaluations', 'AdeN\Api\Modules\Customer\JobConditions\Indicator\Http\Controllers\IndicatorController@getYearEvaluations');


Route::post('customer-jobconditions/indicators/get-level-risks-by-months-list', 'AdeN\Api\Modules\Customer\JobConditions\Indicator\Http\Controllers\IndicatorController@getLevelRisksByMonthsList');
Route::post('customer-jobconditions/indicators/get-interventions', 'AdeN\Api\Modules\Customer\JobConditions\Indicator\Http\Controllers\IndicatorController@getInterventions');
Route::post('customer-jobconditions/indicators/interventions-by-responsibles', 'AdeN\Api\Modules\Customer\JobConditions\Indicator\Http\Controllers\IndicatorController@getInterventionsByResponsibles');

Route::post('customer-jobconditions/indicators/interventions-questions-historical', 'AdeN\Api\Modules\Customer\JobConditions\Indicator\Http\Controllers\IndicatorController@getInterventionsByQuestionsHistorical');

