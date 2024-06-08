<?php
    
	/**
     *Module: CustomerConfigQuestionExpress
     */
    Route::get('customer-config-question-express/get', 'AdeN\Api\Modules\Customer\ConfigQuestionExpress\Http\Controllers\CustomerConfigQuestionExpressController@show');
    Route::post('customer-config-question-express/save', 'AdeN\Api\Modules\Customer\ConfigQuestionExpress\Http\Controllers\CustomerConfigQuestionExpressController@store');
    Route::post('customer-config-question-express/batch', 'AdeN\Api\Modules\Customer\ConfigQuestionExpress\Http\Controllers\CustomerConfigQuestionExpressController@batch');
    Route::post('customer-config-question-express-hazard', 'AdeN\Api\Modules\Customer\ConfigQuestionExpress\Http\Controllers\CustomerConfigQuestionExpressController@showHazard');
    Route::post('customer-config-question-express-hazard-intervention', 'AdeN\Api\Modules\Customer\ConfigQuestionExpress\Http\Controllers\CustomerConfigQuestionExpressController@showHazardIntervention');
    Route::post('customer-config-question-express/delete', 'AdeN\Api\Modules\Customer\ConfigQuestionExpress\Http\Controllers\CustomerConfigQuestionExpressController@destroy');
    Route::post('customer-config-question-express/import', 'AdeN\Api\Modules\Customer\ConfigQuestionExpress\Http\Controllers\CustomerConfigQuestionExpressController@import');
    Route::post('customer-config-question-express/upload', 'AdeN\Api\Modules\Customer\ConfigQuestionExpress\Http\Controllers\CustomerConfigQuestionExpressController@upload');
    Route::match(['get', 'post'], 'customer-config-question-express', 'AdeN\Api\Modules\Customer\ConfigQuestionExpress\Http\Controllers\CustomerConfigQuestionExpressController@index');
	Route::match(['get', 'post'], 'customer-config-question-express/download', 'AdeN\Api\Modules\Customer\ConfigQuestionExpress\Http\Controllers\CustomerConfigQuestionExpressController@download');