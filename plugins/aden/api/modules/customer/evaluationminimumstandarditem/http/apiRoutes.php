<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-evaluation-minimum-standard-item', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItem\Http\Controllers\CustomerEvaluationMinimumStandardItemController@show');
    Route::post('customer-evaluation-minimum-standard-item/save', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItem\Http\Controllers\CustomerEvaluationMinimumStandardItemController@store');
    Route::post('customer-evaluation-minimum-standard-item/update', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItem\Http\Controllers\CustomerEvaluationMinimumStandardItemController@update');
    Route::post('customer-evaluation-minimum-standard-item/delete', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItem\Http\Controllers\CustomerEvaluationMinimumStandardItemController@destroy');
    Route::post('customer-evaluation-minimum-standard-item/import', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItem\Http\Controllers\CustomerEvaluationMinimumStandardItemController@import');
    Route::post('customer-evaluation-minimum-standard-item/upload', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItem\Http\Controllers\CustomerEvaluationMinimumStandardItemController@upload');
    Route::match(['post'], 'customer-evaluation-minimum-standard-item', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItem\Http\Controllers\CustomerEvaluationMinimumStandardItemController@index');
    Route::match(['post'], 'customer-evaluation-minimum-standard-item-question', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItem\Http\Controllers\CustomerEvaluationMinimumStandardItemController@indexQuestion');
	Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-item/download', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItem\Http\Controllers\CustomerEvaluationMinimumStandardItemController@download');