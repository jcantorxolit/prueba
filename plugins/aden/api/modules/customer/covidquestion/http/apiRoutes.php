<?php
    
	/**
     *Module: CustomerCovidQuestion
     */
    Route::get('customer-covid-question/get', 'AdeN\Api\Modules\Customer\CovidQuestion\Http\Controllers\CustomerCovidQuestionController@show');
    Route::post('customer-covid-question/save', 'AdeN\Api\Modules\Customer\CovidQuestion\Http\Controllers\CustomerCovidQuestionController@store');
    Route::post('customer-covid-question/delete', 'AdeN\Api\Modules\Customer\CovidQuestion\Http\Controllers\CustomerCovidQuestionController@destroy');
    Route::post('customer-covid-question/import', 'AdeN\Api\Modules\Customer\CovidQuestion\Http\Controllers\CustomerCovidQuestionController@import');
    Route::post('customer-covid-question/upload', 'AdeN\Api\Modules\Customer\CovidQuestion\Http\Controllers\CustomerCovidQuestionController@upload');
    Route::match(['get', 'post'], 'customer-covid-question', 'AdeN\Api\Modules\Customer\CovidQuestion\Http\Controllers\CustomerCovidQuestionController@index');
	Route::match(['get', 'post'], 'customer-covid-question/download', 'AdeN\Api\Modules\Customer\CovidQuestion\Http\Controllers\CustomerCovidQuestionController@download');