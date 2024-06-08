<?php
    
	/**
     *Module: CovidQuestionGroup
     */
    Route::get('covid-question-group/get', 'AdeN\Api\Modules\Covid\QuestionGroup\Http\Controllers\CovidQuestionGroupController@show');
    Route::post('covid-question-group/save', 'AdeN\Api\Modules\Covid\QuestionGroup\Http\Controllers\CovidQuestionGroupController@store');
    Route::post('covid-question-group/delete', 'AdeN\Api\Modules\Covid\QuestionGroup\Http\Controllers\CovidQuestionGroupController@destroy');
    Route::post('covid-question-group/import', 'AdeN\Api\Modules\Covid\QuestionGroup\Http\Controllers\CovidQuestionGroupController@import');
    Route::post('covid-question-group/upload', 'AdeN\Api\Modules\Covid\QuestionGroup\Http\Controllers\CovidQuestionGroupController@upload');
    Route::match(['get', 'post'], 'covid-question-group', 'AdeN\Api\Modules\Covid\QuestionGroup\Http\Controllers\CovidQuestionGroupController@index');
	Route::match(['get', 'post'], 'covid-question-group/download', 'AdeN\Api\Modules\Covid\QuestionGroup\Http\Controllers\CovidQuestionGroupController@download');