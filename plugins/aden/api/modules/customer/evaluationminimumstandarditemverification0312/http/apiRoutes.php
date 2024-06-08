<?php
    
	/**
     *Module: CustomerEvaluationMinimumStandardItemVerification0312
     */
    Route::get('customer-evaluation-minimum-standard-item-verification-0312/get', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemVerification0312\Http\Controllers\CustomerEvaluationMinimumStandardItemVerification0312Controller@show');
    Route::post('customer-evaluation-minimum-standard-item-verification-0312/save', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemVerification0312\Http\Controllers\CustomerEvaluationMinimumStandardItemVerification0312Controller@store');
    Route::post('customer-evaluation-minimum-standard-item-verification-0312/delete', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemVerification0312\Http\Controllers\CustomerEvaluationMinimumStandardItemVerification0312Controller@destroy');
    Route::post('customer-evaluation-minimum-standard-item-verification-0312/import', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemVerification0312\Http\Controllers\CustomerEvaluationMinimumStandardItemVerification0312Controller@import');
    Route::post('customer-evaluation-minimum-standard-item-verification-0312/upload', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemVerification0312\Http\Controllers\CustomerEvaluationMinimumStandardItemVerification0312Controller@upload');
    Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-item-verification-0312', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemVerification0312\Http\Controllers\CustomerEvaluationMinimumStandardItemVerification0312Controller@index');
	Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-item-verification-0312/download', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemVerification0312\Http\Controllers\CustomerEvaluationMinimumStandardItemVerification0312Controller@download');