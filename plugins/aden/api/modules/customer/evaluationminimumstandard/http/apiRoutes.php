<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-evaluation-minimum-standard', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard\Http\Controllers\CustomerEvaluationMinimumStandardController@show');
    Route::post('customer-evaluation-minimum-standard/save', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard\Http\Controllers\CustomerEvaluationMinimumStandardController@store');
    Route::post('customer-evaluation-minimum-standard/update', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard\Http\Controllers\CustomerEvaluationMinimumStandardController@update');
    Route::post('customer-evaluation-minimum-standard/delete', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard\Http\Controllers\CustomerEvaluationMinimumStandardController@destroy');
    Route::post('customer-evaluation-minimum-standard/import', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard\Http\Controllers\CustomerEvaluationMinimumStandardController@import');
    Route::post('customer-evaluation-minimum-standard/upload', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard\Http\Controllers\CustomerEvaluationMinimumStandardController@upload');
    Route::match(['post'], 'customer-evaluation-minimum-standard', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard\Http\Controllers\CustomerEvaluationMinimumStandardController@index');
    Route::match(['post'], 'customer-evaluation-minimum-standard-summary', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard\Http\Controllers\CustomerEvaluationMinimumStandardController@indexSummary');
	Route::match(['get', 'post'], 'customer-evaluation-minimum-standard/download', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard\Http\Controllers\CustomerEvaluationMinimumStandardController@download');