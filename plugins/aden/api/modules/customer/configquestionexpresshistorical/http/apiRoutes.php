<?php
    
	/**
     *Module: CustomerConfigQuestionExpressHistorical
     */
    Route::get('customer-config-question-express-historical/get', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressHistorical\Http\Controllers\CustomerConfigQuestionExpressHistoricalController@show');
    Route::post('customer-config-question-express-historical/save', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressHistorical\Http\Controllers\CustomerConfigQuestionExpressHistoricalController@store');
    Route::post('customer-config-question-express-historical/delete', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressHistorical\Http\Controllers\CustomerConfigQuestionExpressHistoricalController@destroy');
    Route::post('customer-config-question-express-historical/import', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressHistorical\Http\Controllers\CustomerConfigQuestionExpressHistoricalController@import');
    Route::post('customer-config-question-express-historical/upload', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressHistorical\Http\Controllers\CustomerConfigQuestionExpressHistoricalController@upload');
    Route::match(['get', 'post'], 'customer-config-question-express-historical', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressHistorical\Http\Controllers\CustomerConfigQuestionExpressHistoricalController@index');
	Route::match(['get', 'post'], 'customer-config-question-express-historical/download', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressHistorical\Http\Controllers\CustomerConfigQuestionExpressHistoricalController@download');