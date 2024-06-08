<?php
    
	/**
     *Module: CustomerConfigQuestionExpressIntervention
     */
    Route::get('customer-config-question-express-intervention/get', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressIntervention\Http\Controllers\CustomerConfigQuestionExpressInterventionController@show');
    Route::post('customer-config-question-express-intervention/save', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressIntervention\Http\Controllers\CustomerConfigQuestionExpressInterventionController@store');
    Route::post('customer-config-question-express-intervention/delete', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressIntervention\Http\Controllers\CustomerConfigQuestionExpressInterventionController@destroy');
    Route::post('customer-config-question-express-intervention/import', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressIntervention\Http\Controllers\CustomerConfigQuestionExpressInterventionController@import');
    Route::post('customer-config-question-express-intervention/upload', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressIntervention\Http\Controllers\CustomerConfigQuestionExpressInterventionController@upload');
    Route::match(['get', 'post'], 'customer-config-question-express-intervention', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressIntervention\Http\Controllers\CustomerConfigQuestionExpressInterventionController@index');
    Route::match(['get', 'post'], 'customer-config-question-express-intervention/export-excel', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressIntervention\Http\Controllers\CustomerConfigQuestionExpressInterventionController@exportExcel');    
    Route::match(['get', 'post'], 'customer-config-question-express-intervention/export-excel-general', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressIntervention\Http\Controllers\CustomerConfigQuestionExpressInterventionController@exportGeneralExcel');    
    Route::match(['get', 'post'], 'customer-config-question-express-intervention-responsible', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressIntervention\Http\Controllers\CustomerConfigQuestionExpressInterventionController@indexResponsible');
	Route::match(['get', 'post'], 'customer-config-question-express-intervention/download', 'AdeN\Api\Modules\Customer\ConfigQuestionExpressIntervention\Http\Controllers\CustomerConfigQuestionExpressInterventionController@download');