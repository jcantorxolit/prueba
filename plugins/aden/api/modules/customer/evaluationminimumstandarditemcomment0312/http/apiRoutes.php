<?php
    
	/**
     *Module: CustomerEvaluationMinimumStandardItemComment0312
     */
    Route::get('customer-evaluation-minimum-standard-item-comment-0312/get', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemComment0312\Http\Controllers\CustomerEvaluationMinimumStandardItemComment0312Controller@show');
    Route::post('customer-evaluation-minimum-standard-item-comment-0312/save', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemComment0312\Http\Controllers\CustomerEvaluationMinimumStandardItemComment0312Controller@store');
    Route::post('customer-evaluation-minimum-standard-item-comment-0312/delete', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemComment0312\Http\Controllers\CustomerEvaluationMinimumStandardItemComment0312Controller@destroy');
    Route::post('customer-evaluation-minimum-standard-item-comment-0312/import', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemComment0312\Http\Controllers\CustomerEvaluationMinimumStandardItemComment0312Controller@import');
    Route::post('customer-evaluation-minimum-standard-item-comment-0312/upload', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemComment0312\Http\Controllers\CustomerEvaluationMinimumStandardItemComment0312Controller@upload');
    Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-item-comment-0312', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemComment0312\Http\Controllers\CustomerEvaluationMinimumStandardItemComment0312Controller@index');
	Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-item-comment-0312/download', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemComment0312\Http\Controllers\CustomerEvaluationMinimumStandardItemComment0312Controller@download');