<?php
    
	/**
     *Module: ConfigQuestionExpress
     */
    Route::get('config-question-express/get', 'AdeN\Api\Modules\Config\QuestionExpress\Http\Controllers\ConfigQuestionExpressController@show');
    Route::post('config-question-express/save', 'AdeN\Api\Modules\Config\QuestionExpress\Http\Controllers\ConfigQuestionExpressController@store');
    Route::post('config-question-express/delete', 'AdeN\Api\Modules\Config\QuestionExpress\Http\Controllers\ConfigQuestionExpressController@destroy');
    Route::post('config-question-express/import', 'AdeN\Api\Modules\Config\QuestionExpress\Http\Controllers\ConfigQuestionExpressController@import');
    Route::post('config-question-express/upload', 'AdeN\Api\Modules\Config\QuestionExpress\Http\Controllers\ConfigQuestionExpressController@upload');
    Route::match(['get', 'post'], 'config-question-express', 'AdeN\Api\Modules\Config\QuestionExpress\Http\Controllers\ConfigQuestionExpressController@index');
	Route::match(['get', 'post'], 'config-question-express/download', 'AdeN\Api\Modules\Config\QuestionExpress\Http\Controllers\ConfigQuestionExpressController@download');