<?php
    
	/**
     *Module: CovidQuestion
     */
    Route::get('covid-question/get', 'AdeN\Api\Modules\Covid\Question\Http\Controllers\CovidQuestionController@show');
    Route::post('covid-question/save', 'AdeN\Api\Modules\Covid\Question\Http\Controllers\CovidQuestionController@store');
    Route::post('covid-question/delete', 'AdeN\Api\Modules\Covid\Question\Http\Controllers\CovidQuestionController@destroy');
    Route::post('covid-question/import', 'AdeN\Api\Modules\Covid\Question\Http\Controllers\CovidQuestionController@import');
    Route::post('covid-question/upload', 'AdeN\Api\Modules\Covid\Question\Http\Controllers\CovidQuestionController@upload');
    Route::match(['get', 'post'], 'covid-question', 'AdeN\Api\Modules\Covid\Question\Http\Controllers\CovidQuestionController@index');
	Route::match(['get', 'post'], 'covid-question/download', 'AdeN\Api\Modules\Covid\Question\Http\Controllers\CovidQuestionController@download');