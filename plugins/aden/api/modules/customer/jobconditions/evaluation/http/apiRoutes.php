<?php

/**
 *Module: Job Conditions - Evaluation
 */
Route::post('customer-jobconditions/evaluation/', 'AdeN\Api\Modules\Customer\JobConditions\Evaluation\Http\Controllers\EvaluationController@index');
Route::post('customer-jobconditions/evaluation/show', 'AdeN\Api\Modules\Customer\JobConditions\Evaluation\Http\Controllers\EvaluationController@show');
Route::post('customer-jobconditions/evaluation/save', 'AdeN\Api\Modules\Customer\JobConditions\Evaluation\Http\Controllers\EvaluationController@store');


Route::post('customer-jobconditions/evaluation/get-questions', 'AdeN\Api\Modules\Customer\JobConditions\Evaluation\Http\Controllers\EvaluationController@getQuestions');
Route::post('customer-jobconditions/evaluation/save-answers', 'AdeN\Api\Modules\Customer\JobConditions\Evaluation\Http\Controllers\EvaluationController@storeAnswers');

Route::get('customer-jobconditions/evaluation/periods', 'AdeN\Api\Modules\Customer\JobConditions\Evaluation\Http\Controllers\EvaluationController@getPeriods');
Route::post('customer-jobconditions/evaluation/stats', 'AdeN\Api\Modules\Customer\JobConditions\Evaluation\Http\Controllers\EvaluationController@getStats');


Route::post('customer-jobconditions/evaluation/evidences', 'AdeN\Api\Modules\Customer\JobConditions\Evaluation\Http\Controllers\EvaluationController@getEvidences');
Route::post('customer-jobconditions/evaluation/evidence/upload', 'AdeN\Api\Modules\Customer\JobConditions\Evaluation\Http\Controllers\EvaluationController@upload');
Route::get('customer-jobconditions/evaluation/evidence/download', 'AdeN\Api\Modules\Customer\JobConditions\Evaluation\Http\Controllers\EvaluationController@downloadEvidencesZip');
