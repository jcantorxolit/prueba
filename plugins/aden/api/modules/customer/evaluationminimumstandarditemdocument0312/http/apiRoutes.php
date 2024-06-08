<?php
    
	/**
     *Module: CustomerEvaluationMinimumStandardItemDocument0312
     */
    Route::get('customer-evaluation-minimum-standard-item-document-0312/get', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemDocument0312\Http\Controllers\CustomerEvaluationMinimumStandardItemDocument0312Controller@show');
    Route::post('customer-evaluation-minimum-standard-item-document-0312/save', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemDocument0312\Http\Controllers\CustomerEvaluationMinimumStandardItemDocument0312Controller@store');
    Route::post('customer-evaluation-minimum-standard-item-document-0312/delete', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemDocument0312\Http\Controllers\CustomerEvaluationMinimumStandardItemDocument0312Controller@destroy');
    Route::post('customer-evaluation-minimum-standard-item-document-0312/import', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemDocument0312\Http\Controllers\CustomerEvaluationMinimumStandardItemDocument0312Controller@import');
    Route::post('customer-evaluation-minimum-standard-item-document-0312/import-historical', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemDocument0312\Http\Controllers\CustomerEvaluationMinimumStandardItemDocument0312Controller@importHistorical');
    Route::post('customer-evaluation-minimum-standard-item-document-0312/upload', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemDocument0312\Http\Controllers\CustomerEvaluationMinimumStandardItemDocument0312Controller@upload');
    Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-item-document-0312', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemDocument0312\Http\Controllers\CustomerEvaluationMinimumStandardItemDocument0312Controller@index');
    Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-item-document-0312-available', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemDocument0312\Http\Controllers\CustomerEvaluationMinimumStandardItemDocument0312Controller@indexAvailable');
    Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-item-document-0312-available-previous', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemDocument0312\Http\Controllers\CustomerEvaluationMinimumStandardItemDocument0312Controller@indexAvailablePreviousPeriod');
	Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-item-document-0312/download', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemDocument0312\Http\Controllers\CustomerEvaluationMinimumStandardItemDocument0312Controller@download');